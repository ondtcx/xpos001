<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('catalog.products.index', [
            'products' => Product::query()
                ->with(['category', 'brand', 'variants'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('catalog.products.form', [
            'product' => new Product(['status' => 'active']),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);

        $product = Product::query()->create($validated);

        return redirect()->route('products.edit', $product)->with('status', 'Producto creado. Ahora puedes agregar variantes.');
    }

    public function edit(Product $product): View
    {
        return view('catalog.products.form', [
            'product' => $product,
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validateProduct($request, $product);

        $product->update($validated);

        return redirect()->route('products.index')->with('status', 'Producto actualizado correctamente.');
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'internal_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'internal_code')->ignore($product?->id),
            ],
            'status' => ['required', Rule::in(['active', 'inactive', 'discontinued'])],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
