<?php

namespace App\Support\Sales;

use App\Models\InventoryLot;
use App\Models\SalePresentation;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class PosSaleDraftBuilder
{
    public function build(array $validated): array
    {
        $itemIds = collect($validated['items'] ?? [])->pluck('sale_presentation_id')->filter()->unique()->values();

        $presentations = SalePresentation::query()
            ->with(['variant.product', 'prices' => fn ($query) => $query->orderByDesc('starts_at')])
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        $availableBaseUnitsByVariant = InventoryLot::query()
            ->selectRaw('variant_id, COALESCE(SUM(available_quantity), 0) as available_quantity')
            ->whereIn('variant_id', $presentations->pluck('product_variant_id')->unique()->all())
            ->groupBy('variant_id')
            ->pluck('available_quantity', 'variant_id');

        $lines = [];
        $totalAmount = 0;
        $hasStockWarning = false;
        $hasCostWarning = false;

        foreach ($validated['items'] ?? [] as $item) {
            $presentation = $presentations->get((int) $item['sale_presentation_id']);
            $price = $presentation?->prices->firstWhere('ends_at', null) ?? $presentation?->prices->first();

            if ($presentation === null || $price === null) {
                throw ValidationException::withMessages([
                    'items' => 'Alguna presentación seleccionada ya no está disponible o no tiene precio vigente.',
                ]);
            }

            $quantity = round((float) $item['quantity'], 3);
            $availableBaseUnits = (float) ($availableBaseUnitsByVariant[$presentation->product_variant_id] ?? 0);
            $requiredBaseUnits = $quantity * (float) $presentation->conversion_factor;
            $availableSaleUnits = (float) $presentation->conversion_factor > 0
                ? round($availableBaseUnits / (float) $presentation->conversion_factor, 3)
                : 0;
            $stockWarning = $requiredBaseUnits > $availableBaseUnits;
            $costWarning = $stockWarning || $availableBaseUnits <= 0;
            $lineTotalAmount = (int) round($quantity * $price->price_amount);

            $totalAmount += $lineTotalAmount;
            $hasStockWarning = $hasStockWarning || $stockWarning;
            $hasCostWarning = $hasCostWarning || $costWarning;

            $lines[] = [
                'presentation_id' => $presentation->id,
                'quantity' => $quantity,
                'label' => $presentation->variant->product->name . ' — ' . $presentation->variant->name . ' — ' . $presentation->name,
                'price_amount' => $price->price_amount,
                'line_total_amount' => $lineTotalAmount,
                'available_sale_units' => $availableSaleUnits,
                'has_stock_warning' => $stockWarning,
                'has_cost_warning' => $costWarning,
            ];
        }

        return [
            'items' => $lines,
            'total_amount' => $totalAmount,
            'requires_full_sale' => $hasStockWarning || $hasCostWarning,
            'full_sale_reason' => $this->buildFullSaleReason($hasStockWarning, $hasCostWarning),
        ];
    }

    public function toCreateSalePayload(array $draft, array $validated): array
    {
        $paymentMethod = $validated['payment_method'] ?? 'cash';
        $mixedCash = Money::dollarsToCents($validated['mixed_payments']['cash'] ?? 0);
        $mixedTransfer = Money::dollarsToCents($validated['mixed_payments']['transfer'] ?? 0);
        $receivedAmount = $validated['received_amount'] !== null && $validated['received_amount'] !== ''
            ? Money::dollarsToCents($validated['received_amount'])
            : null;
        $allowCreditSale = (bool) ($validated['allow_credit_sale'] ?? false);
        $confirmCreditSale = (bool) ($validated['confirm_credit_sale'] ?? false);

        if ($paymentMethod === 'mixed' && ($mixedCash + $mixedTransfer) !== $draft['total_amount']) {
            throw ValidationException::withMessages([
                'mixed_payments' => 'En pago mixto, efectivo + transferencia debe cuadrar exactamente con el total.',
            ]);
        }

        if ($paymentMethod === 'cash' && $allowCreditSale) {
            if (empty($validated['customer_id'])) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Debes asignar un cliente antes de convertir una venta de POS a fiado.',
                ]);
            }

            if (! $confirmCreditSale) {
                throw ValidationException::withMessages([
                    'credit_sale' => 'Debes confirmar explícitamente el saldo pendiente antes de cerrar la venta desde POS.',
                ]);
            }
        }

        if ($paymentMethod === 'cash' && $receivedAmount !== null && $receivedAmount < $draft['total_amount'] && ! $allowCreditSale) {
            throw ValidationException::withMessages([
                'received_amount' => 'El monto recibido no puede ser menor al total en ventas de efectivo desde POS.',
            ]);
        }

        if ($allowCreditSale && $paymentMethod !== 'cash') {
            throw ValidationException::withMessages([
                'credit_sale' => 'Por ahora POS solo permite saldo pendiente en ventas de efectivo.',
            ]);
        }

        return [
            'sold_at' => now()->toDateTimeString(),
            'customer_id' => $validated['customer_id'] ?? null,
            'items' => collect($draft['items'])->map(fn (array $item) => [
                'sale_presentation_id' => $item['presentation_id'],
                'quantity' => $item['quantity'],
            ])->all(),
            'payments' => [
                'cash' => match ($paymentMethod) {
                    'cash' => Money::centsToDollars(
                        $allowCreditSale
                            ? min($receivedAmount ?? 0, $draft['total_amount'])
                            : (($receivedAmount !== null && $receivedAmount < $draft['total_amount'])
                            ? $receivedAmount
                            : $draft['total_amount'])
                    ),
                    'mixed' => Money::centsToDollars($mixedCash),
                    default => '0.00',
                },
                'transfer' => match ($paymentMethod) {
                    'transfer' => Money::centsToDollars($draft['total_amount']),
                    'mixed' => Money::centsToDollars($mixedTransfer),
                    default => '0.00',
                },
            ],
        ];
    }

    public function toFullSaleInput(array $draft, array $validated): array
    {
        return [
            'sold_at' => now()->format('Y-m-d\TH:i'),
            'customer_id' => $validated['customer_id'] ?? null,
            'payments' => [
                'cash' => 0,
                'transfer' => 0,
            ],
            'payment_method' => $validated['payment_method'] ?? 'cash',
            'items' => collect($draft['items'])->map(fn (array $item) => [
                'sale_presentation_id' => $item['presentation_id'],
                'quantity' => $item['quantity'],
                'search' => $item['label'],
                'manual_unit_price' => '',
                'manual_price_reason' => '',
            ])->all(),
        ];
    }

    private function buildFullSaleReason(bool $hasStockWarning, bool $hasCostWarning): ?string
    {
        if ($hasStockWarning) {
            return 'Este caso requiere venta completa porque alguna línea supera el stock disponible.';
        }

        if ($hasCostWarning) {
            return 'Este caso requiere venta completa porque alguna línea quedaría con costo pendiente.';
        }

        return null;
    }
}
