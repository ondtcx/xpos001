<?php

namespace App\Http\Controllers;

use App\Models\InventoryLot;
use App\Models\ProductVariant;
use Illuminate\View\View;

class InventoryStockController extends Controller
{
    public function index(): View
    {
        $availableByVariant = InventoryLot::query()
            ->selectRaw('variant_id, SUM(available_quantity) as total_available')
            ->groupBy('variant_id')
            ->pluck('total_available', 'variant_id');

        $variants = ProductVariant::query()
            ->where('is_active', true)
            ->with(['product', 'baseUnit'])
            ->get()
            ->map(function (ProductVariant $variant) use ($availableByVariant) {
                $variant->available_quantity = (float) ($availableByVariant[$variant->id] ?? 0);

                return $variant;
            })
            ->sortBy([
                fn ($variant) => $variant->product->name,
                fn ($variant) => $variant->name,
            ])
            ->values();

        return view('inventory.stock.index', [
            'variants' => $variants,
            'summary' => [
                'total_variants' => $variants->count(),
                'out_of_stock' => $variants->where('available_quantity', '<=', 0)->count(),
                'low_stock' => $variants->whereBetween('available_quantity', [0.001, 5])->count(),
            ],
        ]);
    }
}
