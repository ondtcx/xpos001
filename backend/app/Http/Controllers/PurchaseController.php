<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchases\StoreDetailedPurchaseRequest;
use App\Http\Requests\Purchases\UpdateDetailedPurchaseRequest;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Support\Purchases\CreateQuickPurchaseService;
use App\Support\Purchases\CreateDetailedPurchase;
use App\Support\Purchases\PurchaseCorrectionService;
use App\Support\Purchases\UpdateDetailedPurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(): View
    {
        $correctionService = app(PurchaseCorrectionService::class);
        $purchases = Purchase::query()->with(['supplier', 'creator', 'voider', 'items'])->latest('purchased_at')->get();

        $purchases->each(function (Purchase $purchase) use ($correctionService): void {
            $hasConsumedLots = $correctionService->hasConsumedLots($purchase);

            $purchase->setAttribute('can_edit_detailed', $purchase->isDetailed() && $purchase->isConfirmed() && ! $hasConsumedLots);
            $purchase->setAttribute('can_void_purchase', $purchase->isConfirmed() && ! $hasConsumedLots);
        });

        return view('purchases.index', [
            'purchases' => $purchases,
        ]);
    }

    public function create(): View
    {
        return view('purchases.form', [
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
            'variants' => ProductVariant::query()->with('product')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function show(Purchase $purchase, PurchaseCorrectionService $correctionService): View
    {
        $purchase->load([
            'supplier',
            'creator',
            'voider',
            'items.variant.product',
            'lots.variant.product',
        ]);

        $lotIds = $purchase->lots->pluck('id');

        $consumptionMovements = $lotIds->isEmpty()
            ? collect()
            : InventoryMovement::query()
                ->with('variant.product')
                ->whereIn('lot_id', $lotIds)
                ->where('quantity', '<', 0)
                ->orderBy('movement_at')
                ->get()
                ->groupBy('lot_id');

        return view('purchases.show', [
            'purchase' => $purchase,
            'hasConsumedLots' => $correctionService->hasConsumedLots($purchase),
            'consumptionMovements' => $consumptionMovements,
        ]);
    }

    public function createDetailed(): View
    {
        return view('purchases.detailed-form', [
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
            'variants' => ProductVariant::query()->with('product')->where('is_active', true)->orderBy('name')->get(),
            'purchase' => null,
        ]);
    }

    public function editDetailed(Purchase $purchase, PurchaseCorrectionService $correctionService): View|RedirectResponse
    {
        try {
            $correctionService->assertEditable($purchase);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return redirect()->route('purchases.index')->withErrors($exception->errors());
        }

        $purchase->load('items');

        return view('purchases.detailed-form', [
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
            'variants' => ProductVariant::query()->with('product')->where('is_active', true)->orderBy('name')->get(),
            'purchase' => $purchase,
        ]);
    }

    public function store(Request $request, CreateQuickPurchaseService $creator): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'purchased_at' => ['required', 'date'],
            'payment_type' => ['required', 'string', 'max:50'],
            'is_credit' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'gte:0'],
            'items.*.expiration_date' => ['nullable', 'date'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $purchase = $creator->handle($validated, $request->user()->id);

        return redirect()->route('purchases.index')->with('status', "Compra #{$purchase->id} registrada correctamente.");
    }

    public function storeDetailed(StoreDetailedPurchaseRequest $request, CreateDetailedPurchase $creator): RedirectResponse
    {
        $validated = $request->validated();

        $purchase = $creator->handle($validated, $request->user()->id);

        return redirect()->route('purchases.index')->with('status', "Compra detallada #{$purchase->id} registrada correctamente.");
    }

    public function updateDetailed(UpdateDetailedPurchaseRequest $request, Purchase $purchase, UpdateDetailedPurchase $updater): RedirectResponse
    {
        $validated = $request->validated();

        $purchase = $updater->handle($purchase, $validated, $request->user()->id);

        return redirect()->route('purchases.index')->with('status', "Compra detallada #{$purchase->id} actualizada correctamente.");
    }

    public function void(Request $request, Purchase $purchase, PurchaseCorrectionService $correctionService): RedirectResponse
    {
        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:1000'],
        ]);

        $correctionService->void($purchase, $validated['void_reason'], $request->user()->id);

        return redirect()->route('purchases.index')->with('status', "Compra #{$purchase->id} anulada correctamente.");
    }
}
