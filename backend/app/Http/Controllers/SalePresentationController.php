<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalePresentation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalePresentationController extends Controller
{
    public function index(Product $product, ProductVariant $variant): View
    {
        abort_unless($variant->product_id === $product->id, 404);

        return view('catalog.presentations.index', [
            'product' => $product,
            'variant' => $variant,
            'presentations' => $variant->presentations()->with(['prices' => fn ($query) => $query->orderByDesc('starts_at')])->orderBy('name')->get(),
        ]);
    }

    public function create(Product $product, ProductVariant $variant): View
    {
        abort_unless($variant->product_id === $product->id, 404);

        return view('catalog.presentations.form', [
            'product' => $product,
            'variant' => $variant,
            'presentation' => new SalePresentation(['is_active' => true]),
        ]);
    }

    public function store(Request $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        $validated = $this->validatePresentation($request);
        $validated['product_variant_id'] = $variant->id;

        DB::transaction(function () use ($validated, $variant) {
            if (($validated['is_default'] ?? false) === true) {
                $variant->presentations()->update(['is_default' => false]);
            }

            SalePresentation::query()->create($validated);
        });

        return redirect()->route('products.variants.presentations.index', [$product, $variant])->with('status', 'Presentación creada correctamente.');
    }

    public function edit(Product $product, ProductVariant $variant, SalePresentation $presentation): View
    {
        abort_unless($variant->product_id === $product->id && $presentation->product_variant_id === $variant->id, 404);

        return view('catalog.presentations.form', compact('product', 'variant', 'presentation'));
    }

    public function update(Request $request, Product $product, ProductVariant $variant, SalePresentation $presentation): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id && $presentation->product_variant_id === $variant->id, 404);

        $validated = $this->validatePresentation($request);

        DB::transaction(function () use ($validated, $variant, $presentation) {
            if (($validated['is_default'] ?? false) === true) {
                $variant->presentations()->whereKeyNot($presentation->id)->update(['is_default' => false]);
            }

            $presentation->update($validated);
        });

        return redirect()->route('products.variants.presentations.index', [$product, $variant])->with('status', 'Presentación actualizada correctamente.');
    }

    private function validatePresentation(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'conversion_factor' => ['required', 'numeric', 'gt:0'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
