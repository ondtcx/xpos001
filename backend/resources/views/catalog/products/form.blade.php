<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$product->exists ? 'Editar producto' : 'Nuevo producto'" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="space-y-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @csrf
                @if ($product->exists)
                    @method('PUT')
                @endif

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $product->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="internal_code" class="block text-sm font-medium text-gray-700">Código interno</label>
                        <input id="internal_code" name="internal_code" type="text" value="{{ old('internal_code', $product->internal_code) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('internal_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Categoría</label>
                        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Sin categoría</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="brand_id" class="block text-sm font-medium text-gray-700">Marca</label>
                        <select id="brand_id" name="brand_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Sin marca</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product->brand_id) === (string) $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @foreach (['active' => 'Activo', 'inactive' => 'Inactivo', 'discontinued' => 'Descontinuado'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $product->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notas</label>
                        <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes', $product->notes) }}</textarea>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        @if ($product->exists)
                            <a href="{{ route('products.variants.index', $product) }}" class="text-sm text-emerald-700 hover:text-emerald-900">Administrar variantes</a>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('products.index') }}" class="text-sm text-gray-600">Cancelar</a>
                        <button class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
