<?php

namespace App\Http\Controllers;

use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\OpeningInventoryEntry;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OpeningInventoryController extends Controller
{
    public function index(): View
    {
        return view('inventory.opening.index', [
            'entries' => OpeningInventoryEntry::query()->with('variant.product')->latest('recorded_at')->get(),
        ]);
    }

    public function create(): View
    {
        return view('inventory.opening.form', [
            'variants' => ProductVariant::query()->with('product')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'estimated_unit_cost' => ['required', 'numeric', 'gte:0'],
            'recorded_at' => ['required', 'date'],
            'is_audited' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $costAmount = Money::dollarsToCents($validated['estimated_unit_cost']);

            $entry = OpeningInventoryEntry::query()->create([
                'variant_id' => $validated['variant_id'],
                'quantity' => $validated['quantity'],
                'estimated_unit_cost_amount' => $costAmount,
                'recorded_at' => $validated['recorded_at'],
                'is_audited' => (bool) ($validated['is_audited'] ?? false),
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $lot = InventoryLot::query()->create([
                'variant_id' => $validated['variant_id'],
                'purchase_item_id' => null,
                'origin_type' => 'opening_inventory',
                'origin_id' => $entry->id,
                'received_at' => $validated['recorded_at'],
                'expiration_date' => null,
                'initial_quantity' => $validated['quantity'],
                'available_quantity' => $validated['quantity'],
                'bonus_quantity' => 0,
                'unit_cost_final_amount' => $costAmount,
                'suggested_sale_price_amount' => null,
                'is_estimated' => ! ((bool) ($validated['is_audited'] ?? false)),
                'status' => 'active',
            ]);

            InventoryMovement::query()->create([
                'variant_id' => $validated['variant_id'],
                'lot_id' => $lot->id,
                'movement_type' => 'opening_inventory',
                'quantity' => $validated['quantity'],
                'unit_cost_amount' => $costAmount,
                'reference_type' => 'opening_inventory',
                'reference_id' => $entry->id,
                'movement_at' => $validated['recorded_at'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('opening-inventory.index')->with('status', 'Inventario inicial registrado correctamente.');
    }
}
