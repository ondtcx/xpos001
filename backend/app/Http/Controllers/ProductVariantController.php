<?php

namespace App\Http\Controllers;

use App\Models\BaseUnit;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductVariantController extends Controller
{
    public function index(Product $product): View
    {
        return view('catalog.variants.index', [
            'product' => $product,
            'variants' => $product->variants()->with('baseUnit')->orderBy('name')->get(),
        ]);
    }

    public function create(Product $product): View
    {
        return view('catalog.variants.form', [
            'product' => $product,
            'variant' => new ProductVariant(['is_active' => true]),
            'baseUnits' => BaseUnit::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validateVariant($request);
        $validated['product_id'] = $product->id;

        $variant = ProductVariant::query()->create($validated);

        return redirect()->route('products.variants.edit', [$product, $variant])->with('status', 'Variante creada. Ahora puedes agregar presentaciones.');
    }

    public function edit(Product $product, ProductVariant $variant): View
    {
        abort_unless($variant->product_id === $product->id, 404);

        return view('catalog.variants.form', [
            'product' => $product,
            'variant' => $variant,
            'baseUnits' => BaseUnit::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        $validated = $this->validateVariant($request, $variant);
        $variant->update($validated);

        return redirect()->route('products.variants.index', $product)->with('status', 'Variante actualizada correctamente.');
    }

    private function validateVariant(Request $request, ?ProductVariant $variant = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($variant?->id)],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'barcode')->ignore($variant?->id)],
            'base_unit_id' => ['required', 'exists:base_units,id'],
            'tracks_expiration' => ['nullable', 'boolean'],
            'is_returnable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
