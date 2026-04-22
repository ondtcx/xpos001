<?php

namespace App\Http\Controllers;

use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\SupplierVariantRef;
use App\Support\Money;
use App\Support\Purchases\CreateDetailedPurchase;
use App\Support\Purchases\PurchaseCorrectionService;
use App\Support\Purchases\UpdateDetailedPurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

    public function store(Request $request): RedirectResponse
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

        $purchase = DB::transaction(function () use ($validated, $request) {
            $subtotal = 0;

            $purchase = Purchase::query()->create([
                'supplier_id' => $validated['supplier_id'] ?? null,
                'invoice_number' => $validated['invoice_number'] ?? null,
                'purchased_at' => $validated['purchased_at'],
                'payment_type' => $validated['payment_type'],
                'entry_mode' => Purchase::ENTRY_MODE_QUICK,
                'is_credit' => (bool) ($validated['is_credit'] ?? false),
                'subtotal_amount' => 0,
                'global_discount_amount' => 0,
                'global_tax_amount' => 0,
                'extra_costs_amount' => 0,
                'total_amount' => 0,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                $unitCostAmount = Money::dollarsToCents($item['unit_cost']);
                $lineSubtotal = (int) round(((float) $item['quantity']) * $unitCostAmount);
                $subtotal += $lineSubtotal;

                $purchaseItem = PurchaseItem::query()->create([
                    'purchase_id' => $purchase->id,
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost_base_amount' => $unitCostAmount,
                    'line_subtotal_amount' => $lineSubtotal,
                    'line_discount_amount' => 0,
                    'tax_vat_amount' => 0,
                    'tax_fixed_amount' => 0,
                    'tax_other_amount' => 0,
                    'gift_quantity' => 0,
                    'total_cost_amount' => $lineSubtotal,
                    'expiration_date' => $item['expiration_date'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);

                $lot = InventoryLot::query()->create([
                    'variant_id' => $item['variant_id'],
                    'purchase_item_id' => $purchaseItem->id,
                    'origin_type' => 'purchase',
                    'origin_id' => $purchase->id,
                    'received_at' => $validated['purchased_at'],
                    'expiration_date' => $item['expiration_date'] ?? null,
                    'initial_quantity' => $item['quantity'],
                    'available_quantity' => $item['quantity'],
                    'bonus_quantity' => 0,
                    'unit_cost_final_amount' => $unitCostAmount,
                    'suggested_sale_price_amount' => null,
                    'is_estimated' => false,
                    'status' => 'active',
                ]);

                InventoryMovement::query()->create([
                    'variant_id' => $item['variant_id'],
                    'lot_id' => $lot->id,
                    'movement_type' => 'purchase_entry',
                    'quantity' => $item['quantity'],
                    'unit_cost_amount' => $unitCostAmount,
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'movement_at' => $validated['purchased_at'],
                    'notes' => $item['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                if (! empty($validated['supplier_id'])) {
                    SupplierVariantRef::query()->updateOrCreate(
                        [
                            'supplier_id' => $validated['supplier_id'],
                            'variant_id' => $item['variant_id'],
                        ],
                        [
                            'last_purchase_price_amount' => $unitCostAmount,
                            'last_purchase_at' => $validated['purchased_at'],
                        ],
                    );
                }
            }

            $purchase->update([
                'subtotal_amount' => $subtotal,
                'total_amount' => $subtotal,
            ]);

            return $purchase;
        });

        return redirect()->route('purchases.index')->with('status', "Compra #{$purchase->id} registrada correctamente.");
    }

    public function storeDetailed(Request $request, CreateDetailedPurchase $creator): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'invoice_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('purchases')->where(function ($query) use ($request) {
                    return $query->where('supplier_id', $request->input('supplier_id'));
                }),
            ],
            'purchased_at' => ['required', 'date'],
            'payment_type' => ['required', Rule::in(['cash', 'transfer', 'credit'])],
            'global_discount_amount' => ['nullable', 'numeric', 'gte:0'],
            'global_tax_iva_amount' => ['nullable', 'numeric', 'gte:0'],
            'global_tax_ice_amount' => ['nullable', 'numeric', 'gte:0'],
            'global_tax_other_amount' => ['nullable', 'numeric', 'gte:0'],
            'extra_costs_amount' => ['nullable', 'numeric', 'gte:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.line_type' => ['required', Rule::in([PurchaseItem::LINE_TYPE_NORMAL, PurchaseItem::LINE_TYPE_BONUS])],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.bonus_quantity' => ['nullable', 'numeric', 'gte:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'items.*.manual_total_cost' => ['nullable', 'numeric', 'gte:0'],
            'items.*.line_discount_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.tax_iva_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.tax_ice_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.tax_other_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.eligible_for_global_iva' => ['nullable', 'boolean'],
            'items.*.eligible_for_global_ice' => ['nullable', 'boolean'],
            'items.*.eligible_for_global_other' => ['nullable', 'boolean'],
            'items.*.expiration_date' => ['nullable', 'date'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        foreach ($validated['items'] as $index => $item) {
            if (($item['line_type'] ?? PurchaseItem::LINE_TYPE_NORMAL) === PurchaseItem::LINE_TYPE_NORMAL && ! array_key_exists('unit_cost', $item)) {
                return back()->withErrors(["items.$index.unit_cost" => 'El costo unitario es obligatorio en líneas normales.'])->withInput();
            }

            if (($item['line_type'] ?? PurchaseItem::LINE_TYPE_NORMAL) === PurchaseItem::LINE_TYPE_BONUS && ! array_key_exists('manual_total_cost', $item)) {
                return back()->withErrors(["items.$index.manual_total_cost" => 'El costo manual total es obligatorio en líneas de bonificación.'])->withInput();
            }
        }

        $purchase = $creator->handle($validated, $request->user()->id);

        return redirect()->route('purchases.index')->with('status', "Compra detallada #{$purchase->id} registrada correctamente.");
    }

    public function updateDetailed(Request $request, Purchase $purchase, UpdateDetailedPurchase $updater): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'invoice_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('purchases')->ignore($purchase->id)->where(function ($query) use ($request) {
                    return $query->where('supplier_id', $request->input('supplier_id'));
                }),
            ],
            'purchased_at' => ['required', 'date'],
            'payment_type' => ['required', Rule::in(['cash', 'transfer', 'credit'])],
            'global_discount_amount' => ['nullable', 'numeric', 'gte:0'],
            'global_tax_iva_amount' => ['nullable', 'numeric', 'gte:0'],
            'global_tax_ice_amount' => ['nullable', 'numeric', 'gte:0'],
            'global_tax_other_amount' => ['nullable', 'numeric', 'gte:0'],
            'extra_costs_amount' => ['nullable', 'numeric', 'gte:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.line_type' => ['required', Rule::in([PurchaseItem::LINE_TYPE_NORMAL, PurchaseItem::LINE_TYPE_BONUS])],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.bonus_quantity' => ['nullable', 'numeric', 'gte:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'items.*.manual_total_cost' => ['nullable', 'numeric', 'gte:0'],
            'items.*.line_discount_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.tax_iva_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.tax_ice_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.tax_other_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.eligible_for_global_iva' => ['nullable', 'boolean'],
            'items.*.eligible_for_global_ice' => ['nullable', 'boolean'],
            'items.*.eligible_for_global_other' => ['nullable', 'boolean'],
            'items.*.expiration_date' => ['nullable', 'date'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        foreach ($validated['items'] as $index => $item) {
            if (($item['line_type'] ?? PurchaseItem::LINE_TYPE_NORMAL) === PurchaseItem::LINE_TYPE_NORMAL && ! array_key_exists('unit_cost', $item)) {
                return back()->withErrors(["items.$index.unit_cost" => 'El costo unitario es obligatorio en líneas normales.'])->withInput();
            }

            if (($item['line_type'] ?? PurchaseItem::LINE_TYPE_NORMAL) === PurchaseItem::LINE_TYPE_BONUS && ! array_key_exists('manual_total_cost', $item)) {
                return back()->withErrors(["items.$index.manual_total_cost" => 'El costo manual total es obligatorio en líneas de bonificación.'])->withInput();
            }
        }

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
