<?php

namespace App\Http\Controllers;

use App\Models\Customer;
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
use Illuminate\Http\JsonResponse;
use App\Support\Sales\VoidSaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(): View
    {
        $voidSaleService = app(VoidSaleService::class);
        $sales = Sale::query()->with(['customer', 'creator', 'voider', 'payments', 'receivable.payments'])->latest('sold_at')->get();

        $sales->each(function (Sale $sale) use ($voidSaleService): void {
            try {
                $voidSaleService->validate($sale);
                $sale->setAttribute('can_void_sale', true);
            } catch (ValidationException) {
                $sale->setAttribute('can_void_sale', false);
            }
        });

        return view('sales.index', [
            'sales' => $sales,
        ]);
    }

    public function create(): View
    {
        return view('sales.form', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'presentations' => SalePresentation::query()->with(['variant.product', 'prices' => fn ($q) => $q->orderByDesc('starts_at')])->where('is_active', true)->orderBy('name')->get(),
            'currentCashSession' => CashSession::query()->where('status', 'open')->latest('opened_at')->first(),
        ]);
    }

    public function show(Sale $sale): View
    {
        $sale->load([
            'customer',
            'creator',
            'voider',
            'cashSession',
            'items.presentation',
            'items.variant.product',
            'items.lotConsumptions.lot',
            'payments',
            'receivable.customer',
            'receivable.payments.creator',
        ]);

        return view('sales.show', [
            'sale' => $sale,
            'receivable' => $sale->receivable->first(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        $term = trim($validated['q']);
        $normalized = mb_strtolower($term);

        $presentations = SalePresentation::query()
            ->with([
                'variant.product',
                'prices' => fn ($query) => $query->orderByDesc('starts_at'),
            ])
            ->where('is_active', true)
            ->whereHas('variant', function ($query) use ($term, $normalized) {
                $query->where('is_active', true)
                    ->where(function ($nested) use ($term, $normalized) {
                        $nested->whereRaw('LOWER(name) LIKE ?', ['%' . $normalized . '%'])
                            ->orWhereRaw('LOWER(barcode) = ?', [$normalized])
                            ->orWhereHas('product', function ($productQuery) use ($term, $normalized) {
                                $productQuery->whereRaw('LOWER(name) LIKE ?', ['%' . $normalized . '%'])
                                    ->orWhereRaw('LOWER(internal_code) = ?', [$normalized]);
                            });
                    });
            })
            ->limit(8)
            ->get();

        $results = $presentations->map(function (SalePresentation $presentation) use ($normalized) {
            $price = $presentation->prices->firstWhere('ends_at', null) ?? $presentation->prices->first();
            $product = $presentation->variant->product;
            $barcode = $presentation->variant->barcode;
            $internalCode = $product->internal_code;
            $availableBaseUnits = (float) InventoryLot::query()
                ->where('variant_id', $presentation->product_variant_id)
                ->sum('available_quantity');
            $availableSaleUnits = (float) $presentation->conversion_factor > 0
                ? round($availableBaseUnits / (float) $presentation->conversion_factor, 3)
                : 0;
            $isExactCodeMatch = ($barcode !== null && mb_strtolower($barcode) === $normalized)
                || ($internalCode !== null && mb_strtolower($internalCode) === $normalized);

            return [
                'id' => $presentation->id,
                'label' => $product->name . ' — ' . $presentation->variant->name . ' — ' . $presentation->name,
                'price' => $price ? Money::centsToDollars($price->price_amount) : null,
                'available_sale_units' => number_format($availableSaleUnits, 3, '.', ''),
                'barcode' => $barcode,
                'internal_code' => $internalCode,
                'exact_code_match' => $isExactCodeMatch,
            ];
        })->values();

        return response()->json([
            'results' => $results,
            'auto_select' => $results->contains(fn (array $result) => $result['exact_code_match']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sold_at' => ['required', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'notes' => ['nullable', 'string'],
            'confirm_stock_warnings' => ['nullable', 'boolean'],
            'confirm_cost_warnings' => ['nullable', 'boolean'],
            'payments.cash' => ['nullable', 'numeric', 'gte:0'],
            'payments.transfer' => ['nullable', 'numeric', 'gte:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_presentation_id' => ['required', 'exists:sale_presentations,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.manual_unit_price' => ['nullable', 'numeric', 'gt:0'],
            'items.*.manual_price_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $cash = Money::dollarsToCents($validated['payments']['cash'] ?? 0);
        $transfer = Money::dollarsToCents($validated['payments']['transfer'] ?? 0);
        $paidAmount = $cash + $transfer;
        $currentCashSession = CashSession::query()->where('status', 'open')->latest('opened_at')->first();

        if ($paidAmount > 0 && $currentCashSession === null) {
            throw ValidationException::withMessages([
                'payments' => 'Debes abrir una caja antes de registrar pagos en ventas.',
            ]);
        }

        $sale = DB::transaction(function () use ($validated, $paidAmount, $cash, $transfer, $request, $currentCashSession) {
            $subtotal = 0;

            $sale = Sale::query()->create([
                'sold_at' => $validated['sold_at'],
                'customer_id' => $validated['customer_id'] ?? null,
                'cash_session_id' => $currentCashSession?->id,
                'subtotal_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
                'paid_amount' => 0,
                'credit_amount' => 0,
                'status' => 'confirmed',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                $presentation = SalePresentation::query()->with(['variant', 'prices' => fn ($q) => $q->orderByDesc('starts_at')])->findOrFail($item['sale_presentation_id']);
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
                $subtotal += $subtotalItem;

                $saleItem = SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'item_type' => 'product',
                    'sale_presentation_id' => $presentation->id,
                    'variant_id' => $presentation->product_variant_id,
                    'description_snapshot' => $presentation->variant->product->name . ' — ' . $presentation->variant->name . ' — ' . $presentation->name,
                    'quantity' => $quantity,
                    'unit_price_amount' => $unitPriceAmount,
                    'original_unit_price_amount' => $price->price_amount,
                    'manual_unit_price_amount' => $hasManualOverride ? $manualUnitPriceAmount : null,
                    'has_manual_price_override' => $hasManualOverride,
                    'manual_price_reason' => $hasManualOverride ? $item['manual_price_reason'] : null,
                    'subtotal_amount' => $subtotalItem,
                    'total_cost_amount' => 0,
                    'total_profit_amount' => 0,
                    'has_cost_warning' => false,
                    'has_stock_warning' => false,
                    'stock_warning_acknowledged' => false,
                    'cost_warning_acknowledged' => false,
                ]);

                $requiredBaseUnits = $quantity * (float) $presentation->conversion_factor;
                $remaining = $requiredBaseUnits;
                $costForItem = 0;
                $hasCostWarning = false;
                $hasStockWarning = false;

                $lots = InventoryLot::query()
                    ->where('variant_id', $presentation->product_variant_id)
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
                        'variant_id' => $presentation->product_variant_id,
                        'lot_id' => $lot->id,
                        'movement_type' => 'sale_output',
                        'quantity' => -$consumed,
                        'unit_cost_amount' => $lot->unit_cost_final_amount,
                        'reference_type' => 'sale',
                        'reference_id' => $sale->id,
                        'movement_at' => $validated['sold_at'],
                        'notes' => $saleItem->description_snapshot,
                        'created_by' => $request->user()->id,
                    ]);

                    $remaining -= $consumed;
                }

                if ($remaining > 0) {
                    $hasStockWarning = true;
                    $hasCostWarning = true;
                }

                if ($costForItem <= 0) {
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

                if ($hasManualOverride && $unitPriceAmount < (int) round($costForItem / max($quantity, 1))) {
                    throw ValidationException::withMessages([
                        'items' => 'No se permite vender por debajo del costo calculado para la línea.',
                    ]);
                }

                $saleItem->update([
                    'total_cost_amount' => $costForItem,
                    'total_profit_amount' => $subtotalItem - $costForItem,
                    'has_cost_warning' => $hasCostWarning,
                    'has_stock_warning' => $hasStockWarning,
                    'stock_warning_acknowledged' => $hasStockWarning ? (bool) ($validated['confirm_stock_warnings'] ?? false) : false,
                    'cost_warning_acknowledged' => $hasCostWarning ? (bool) ($validated['confirm_cost_warnings'] ?? false) : false,
                ]);

            }

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

            if ($cash > 0) {
                $payment = SalePayment::query()->create([
                    'sale_id' => $sale->id,
                    'payment_method' => 'cash',
                    'amount' => min($cash, $subtotal),
                    'received_at' => $validated['sold_at'],
                ]);

                if ($sale->cash_session_id !== null) {
                    CashMovement::query()->create([
                        'cash_session_id' => $sale->cash_session_id,
                        'movement_type' => 'sale_payment',
                        'amount' => $payment->amount,
                        'payment_method' => 'cash',
                        'reference_type' => 'sale',
                        'reference_id' => $sale->id,
                        'notes' => 'Pago en efectivo de venta',
                        'created_by' => $request->user()->id,
                        'created_at' => $validated['sold_at'],
                    ]);
                }
            }

            if ($transfer > 0) {
                $remainingPaid = max(min($paidAmount, $subtotal) - min($cash, $subtotal), 0);

                if ($remainingPaid > 0) {
                    $payment = SalePayment::query()->create([
                        'sale_id' => $sale->id,
                        'payment_method' => 'transfer',
                        'amount' => $remainingPaid,
                        'received_at' => $validated['sold_at'],
                    ]);

                    if ($sale->cash_session_id !== null) {
                        CashMovement::query()->create([
                            'cash_session_id' => $sale->cash_session_id,
                            'movement_type' => 'sale_payment',
                            'amount' => $payment->amount,
                            'payment_method' => 'transfer',
                            'reference_type' => 'sale',
                            'reference_id' => $sale->id,
                            'notes' => 'Pago por transferencia de venta',
                            'created_by' => $request->user()->id,
                            'created_at' => $validated['sold_at'],
                        ]);
                    }
                }
            }

            if ($creditAmount > 0) {
                Receivable::query()->create([
                    'customer_id' => $validated['customer_id'],
                    'sale_id' => $sale->id,
                    'original_amount' => $creditAmount,
                    'pending_amount' => $creditAmount,
                    'opened_at' => $validated['sold_at'],
                    'status' => 'open',
                ]);
            }

            return $sale;
        });

        return redirect()->route('sales.index')->with('status', "Venta #{$sale->id} registrada correctamente.");
    }

    public function void(Request $request, Sale $sale, VoidSaleService $voidSaleService): RedirectResponse
    {
        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:1000'],
        ]);

        $voidSaleService->handle($sale, $validated['void_reason'], $request->user()->id);

        return redirect()->route('sales.index')->with('status', "Venta #{$sale->id} anulada correctamente.");
    }
}
