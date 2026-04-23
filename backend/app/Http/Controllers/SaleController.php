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
use App\Support\Sales\CreateSaleService;
use Illuminate\Http\JsonResponse;
use App\Support\Sales\VoidSaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $presentations = SalePresentation::query()
            ->with(['variant.product', 'prices' => fn ($q) => $q->orderByDesc('starts_at')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $availableBaseUnitsByVariant = InventoryLot::query()
            ->selectRaw('variant_id, COALESCE(SUM(available_quantity), 0) as available_quantity')
            ->whereIn('variant_id', $presentations->pluck('product_variant_id')->unique()->all())
            ->groupBy('variant_id')
            ->pluck('available_quantity', 'variant_id');

        return view('sales.form', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'presentations' => $presentations,
            'availableBaseUnitsByVariant' => $availableBaseUnitsByVariant,
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

    public function store(Request $request, CreateSaleService $createSaleService): RedirectResponse
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

        $sale = $createSaleService->handle($validated, $request->user()->id, $currentCashSession);

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
