<?php

namespace App\Support\Sales;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleItemLotConsumption;
use App\Models\SalePayment;
use App\Models\SalePresentation;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSaleService
{
    public function handle(array $validated, int $userId, ?CashSession $currentCashSession): Sale
    {
        $cash = Money::dollarsToCents($validated['payments']['cash'] ?? 0);
        $transfer = Money::dollarsToCents($validated['payments']['transfer'] ?? 0);
        $paidAmount = $cash + $transfer;

        return DB::transaction(function () use ($validated, $userId, $currentCashSession, $cash, $transfer, $paidAmount) {
            $sale = $this->createDraftSale($validated, $userId, $currentCashSession);
            $subtotal = $this->createSaleItems($sale, $validated, $userId);
            $creditAmount = $this->finalizeAmounts($sale, $validated, $subtotal, $paidAmount);

            $this->registerPayments($sale, $validated, $userId, $cash, $transfer, $subtotal);
            $this->registerReceivable($sale, $validated, $creditAmount);

            return $sale;
        });
    }

    private function createDraftSale(array $validated, int $userId, ?CashSession $currentCashSession): Sale
    {
        return Sale::query()->create([
            'sold_at' => $validated['sold_at'],
            'customer_id' => $validated['customer_id'] ?? null,
            'cash_session_id' => $currentCashSession?->id,
            'subtotal_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'paid_amount' => 0,
            'credit_amount' => 0,
            'status' => Sale::STATUS_CONFIRMED,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $userId,
        ]);
    }

    private function createSaleItems(Sale $sale, array $validated, int $userId): int
    {
        $subtotal = 0;

        foreach ($validated['items'] as $item) {
            $preparedItem = $this->prepareSaleItemData($item);
            $subtotal += $preparedItem['subtotal_item'];

            $saleItem = SaleItem::query()->create([
                'sale_id' => $sale->id,
                'item_type' => 'product',
                'sale_presentation_id' => $preparedItem['presentation']->id,
                'variant_id' => $preparedItem['presentation']->product_variant_id,
                'description_snapshot' => $preparedItem['description_snapshot'],
                'quantity' => $preparedItem['quantity'],
                'unit_price_amount' => $preparedItem['unit_price_amount'],
                'original_unit_price_amount' => $preparedItem['price_amount'],
                'manual_unit_price_amount' => $preparedItem['has_manual_override'] ? $preparedItem['manual_unit_price_amount'] : null,
                'has_manual_price_override' => $preparedItem['has_manual_override'],
                'manual_price_reason' => $preparedItem['has_manual_override'] ? $preparedItem['manual_price_reason'] : null,
                'subtotal_amount' => $preparedItem['subtotal_item'],
                'total_cost_amount' => 0,
                'total_profit_amount' => 0,
                'has_cost_warning' => false,
                'has_stock_warning' => false,
                'stock_warning_acknowledged' => false,
                'cost_warning_acknowledged' => false,
            ]);

            $consumption = $this->consumeLotsForSaleItem($sale, $saleItem, $preparedItem, $validated['sold_at'], $userId);

            $this->applyWarningsAndCosts($saleItem, $preparedItem, $validated, $consumption);
        }

        return $subtotal;
    }

    private function prepareSaleItemData(array $item): array
    {
        $presentation = SalePresentation::query()
            ->with(['variant.product', 'prices' => fn ($q) => $q->orderByDesc('starts_at')])
            ->findOrFail($item['sale_presentation_id']);

        $price = $presentation->prices->firstWhere('ends_at', null) ?? $presentation->prices->first();
        abort_if($price === null, 422, 'La presentación seleccionada no tiene precio vigente.');

        $quantity = (float) $item['quantity'];
        $manualUnitPriceAmount = array_key_exists('manual_unit_price', $item) && $item['manual_unit_price'] !== null && $item['manual_unit_price'] !== ''
            ? Money::dollarsToCents($item['manual_unit_price'])
            : null;
        $hasManualOverride = $manualUnitPriceAmount !== null && $manualUnitPriceAmount !== $price->price_amount;

        if ($hasManualOverride && empty($item['manual_price_reason'])) {
            throw ValidationException::withMessages([
                'items' => 'Debes indicar el motivo del cambio manual de precio.',
            ]);
        }

        $unitPriceAmount = $hasManualOverride ? $manualUnitPriceAmount : $price->price_amount;
        $subtotalItem = (int) round($quantity * $unitPriceAmount);

        return [
            'presentation' => $presentation,
            'price_amount' => $price->price_amount,
            'quantity' => $quantity,
            'manual_unit_price_amount' => $manualUnitPriceAmount,
            'has_manual_override' => $hasManualOverride,
            'manual_price_reason' => $item['manual_price_reason'] ?? null,
            'unit_price_amount' => $unitPriceAmount,
            'subtotal_item' => $subtotalItem,
            'description_snapshot' => $presentation->variant->product->name . ' — ' . $presentation->variant->name . ' — ' . $presentation->name,
        ];
    }

    private function consumeLotsForSaleItem(Sale $sale, SaleItem $saleItem, array $preparedItem, string $soldAt, int $userId): array
    {
        $requiredBaseUnits = $preparedItem['quantity'] * (float) $preparedItem['presentation']->conversion_factor;
        $remaining = $requiredBaseUnits;
        $costForItem = 0;

        $lots = InventoryLot::query()
            ->where('variant_id', $preparedItem['presentation']->product_variant_id)
            ->where('available_quantity', '>', 0)
            ->orderBy('received_at')
            ->orderBy('id')
            ->get();

        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }

            $available = (float) $lot->available_quantity;
            $consumed = min($available, $remaining);
            $lotCost = (int) round($consumed * $lot->unit_cost_final_amount);
            $costForItem += $lotCost;

            SaleItemLotConsumption::query()->create([
                'sale_item_id' => $saleItem->id,
                'lot_id' => $lot->id,
                'quantity' => $consumed,
                'unit_cost_amount' => $lot->unit_cost_final_amount,
                'total_cost_amount' => $lotCost,
            ]);

            $lot->update([
                'available_quantity' => round($available - $consumed, 3),
                'status' => round($available - $consumed, 3) > 0 ? 'active' : 'depleted',
            ]);

            InventoryMovement::query()->create([
                'variant_id' => $preparedItem['presentation']->product_variant_id,
                'lot_id' => $lot->id,
                'movement_type' => 'sale_output',
                'quantity' => -$consumed,
                'unit_cost_amount' => $lot->unit_cost_final_amount,
                'reference_type' => 'sale',
                'reference_id' => $sale->id,
                'movement_at' => $soldAt,
                'notes' => $saleItem->description_snapshot,
                'created_by' => $userId,
            ]);

            $remaining -= $consumed;
        }

        return [
            'remaining' => $remaining,
            'cost_for_item' => $costForItem,
        ];
    }

    private function applyWarningsAndCosts(SaleItem $saleItem, array $preparedItem, array $validated, array $consumption): void
    {
        $hasStockWarning = false;
        $hasCostWarning = false;

        if ($consumption['remaining'] > 0) {
            $hasStockWarning = true;
            $hasCostWarning = true;
        }

        if ($consumption['cost_for_item'] <= 0) {
            $hasCostWarning = true;
        }

        if ($hasStockWarning && ! ($validated['confirm_stock_warnings'] ?? false)) {
            throw ValidationException::withMessages([
                'warnings' => 'Debes confirmar explícitamente la venta con stock insuficiente antes de guardar.',
            ]);
        }

        if ($hasCostWarning && ! ($validated['confirm_cost_warnings'] ?? false)) {
            throw ValidationException::withMessages([
                'warnings' => 'Debes confirmar explícitamente la venta con costo pendiente antes de guardar.',
            ]);
        }

        if ($preparedItem['has_manual_override'] && $preparedItem['unit_price_amount'] < (int) round($consumption['cost_for_item'] / max($preparedItem['quantity'], 1))) {
            throw ValidationException::withMessages([
                'items' => 'No se permite vender por debajo del costo calculado para la línea.',
            ]);
        }

        $saleItem->update([
            'total_cost_amount' => $consumption['cost_for_item'],
            'total_profit_amount' => $preparedItem['subtotal_item'] - $consumption['cost_for_item'],
            'has_cost_warning' => $hasCostWarning,
            'has_stock_warning' => $hasStockWarning,
            'stock_warning_acknowledged' => $hasStockWarning ? (bool) ($validated['confirm_stock_warnings'] ?? false) : false,
            'cost_warning_acknowledged' => $hasCostWarning ? (bool) ($validated['confirm_cost_warnings'] ?? false) : false,
        ]);
    }

    private function finalizeAmounts(Sale $sale, array $validated, int $subtotal, int $paidAmount): int
    {
        if ($paidAmount > $subtotal) {
            throw ValidationException::withMessages([
                'payments' => 'El total pagado no puede exceder el total de la venta.',
            ]);
        }

        $creditAmount = max($subtotal - $paidAmount, 0);

        if ($creditAmount > 0 && empty($validated['customer_id'])) {
            throw ValidationException::withMessages([
                'customer_id' => 'No se puede dejar saldo pendiente sin cliente.',
            ]);
        }

        $sale->update([
            'subtotal_amount' => $subtotal,
            'total_amount' => $subtotal,
            'paid_amount' => min($paidAmount, $subtotal),
            'credit_amount' => $creditAmount,
        ]);

        return $creditAmount;
    }

    private function registerPayments(Sale $sale, array $validated, int $userId, int $cash, int $transfer, int $subtotal): void
    {
        if ($cash > 0) {
            $payment = SalePayment::query()->create([
                'sale_id' => $sale->id,
                'payment_method' => 'cash',
                'amount' => min($cash, $subtotal),
                'received_at' => $validated['sold_at'],
            ]);

            $this->createCashMovement($sale, $payment->amount, 'cash', 'Pago en efectivo de venta', $validated['sold_at'], $userId);
        }

        if ($transfer > 0) {
            $remainingPaid = max(min($cash + $transfer, $subtotal) - min($cash, $subtotal), 0);

            if ($remainingPaid > 0) {
                $payment = SalePayment::query()->create([
                    'sale_id' => $sale->id,
                    'payment_method' => 'transfer',
                    'amount' => $remainingPaid,
                    'received_at' => $validated['sold_at'],
                ]);

                $this->createCashMovement($sale, $payment->amount, 'transfer', 'Pago por transferencia de venta', $validated['sold_at'], $userId);
            }
        }
    }

    private function createCashMovement(Sale $sale, int $amount, string $paymentMethod, string $notes, string $soldAt, int $userId): void
    {
        if ($sale->cash_session_id === null) {
            return;
        }

        CashMovement::query()->create([
            'cash_session_id' => $sale->cash_session_id,
            'movement_type' => 'sale_payment',
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
            'notes' => $notes,
            'created_by' => $userId,
            'created_at' => $soldAt,
        ]);
    }

    private function registerReceivable(Sale $sale, array $validated, int $creditAmount): void
    {
        if ($creditAmount <= 0) {
            return;
        }

        Receivable::query()->create([
            'customer_id' => $validated['customer_id'],
            'sale_id' => $sale->id,
            'original_amount' => $creditAmount,
            'pending_amount' => $creditAmount,
            'opened_at' => $validated['sold_at'],
            'status' => 'open',
        ]);
    }
}
