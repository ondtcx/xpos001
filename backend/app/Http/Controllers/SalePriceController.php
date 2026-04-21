<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalePresentation;
use App\Models\SalePrice;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalePriceController extends Controller
{
    public function index(Product $product, ProductVariant $variant, SalePresentation $presentation): View
    {
        abort_unless(
            $variant->product_id === $product->id && $presentation->product_variant_id === $variant->id,
            404,
        );

        return view('catalog.prices.index', [
            'product' => $product,
            'variant' => $variant,
            'presentation' => $presentation,
            'prices' => $presentation->prices()->with('creator')->orderByDesc('starts_at')->get(),
        ]);
    }

    public function create(Product $product, ProductVariant $variant, SalePresentation $presentation): View
    {
        abort_unless(
            $variant->product_id === $product->id && $presentation->product_variant_id === $variant->id,
            404,
        );

        return view('catalog.prices.form', [
            'product' => $product,
            'variant' => $variant,
            'presentation' => $presentation,
        ]);
    }

    public function store(Request $request, Product $product, ProductVariant $variant, SalePresentation $presentation): RedirectResponse
    {
        abort_unless(
            $variant->product_id === $product->id && $presentation->product_variant_id === $variant->id,
            404,
        );

        $validated = $request->validate([
            'price' => ['required', 'numeric', 'gt:0'],
            'min_price' => ['nullable', 'numeric', 'gte:0'],
            'suggested_margin_percent' => ['nullable', 'numeric', 'gte:0'],
            'starts_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated, $presentation, $request) {
            $startsAt = $validated['starts_at'];

            $presentation->prices()
                ->whereNull('ends_at')
                ->update(['ends_at' => $startsAt]);

            SalePrice::query()->create([
                'sale_presentation_id' => $presentation->id,
                'price_amount' => Money::dollarsToCents($validated['price']),
                'min_price_amount' => filled($validated['min_price'] ?? null)
                    ? Money::dollarsToCents($validated['min_price'])
                    : null,
                'suggested_margin_percent' => $validated['suggested_margin_percent'] ?? null,
                'starts_at' => $startsAt,
                'ends_at' => null,
                'created_by' => $request->user()->id,
                'reason' => $validated['reason'] ?? null,
            ]);
        });

        return redirect()->route('products.variants.presentations.prices.index', [$product, $variant, $presentation])
            ->with('status', 'Precio registrado correctamente.');
    }
}
