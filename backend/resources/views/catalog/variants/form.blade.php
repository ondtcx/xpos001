<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="($variant->exists ? 'Editar variante' : 'Nueva variante') . ' — ' . $product->name" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <form method="POST" action="{{ $variant->exists ? route('products.variants.update', [$product, $variant]) : route('products.variants.store', $product) }}" class="space-y-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-catalog-border">
                @csrf
                @if ($variant->exists)
                    @method('PUT')
                @endif

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre de variante</label>
                        <input name="name" type="text" value="{{ old('name', $variant->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Unidad base</label>
                        <select name="base_unit_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="">Selecciona una unidad</option>
                            @foreach ($baseUnits as $unit)
                                <option value="{{ $unit->id }}" @selected((string) old('base_unit_id', $variant->base_unit_id) === (string) $unit->id)>{{ $unit->name }} ({{ $unit->symbol }})</option>
                            @endforeach
                        </select>
                        @error('base_unit_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">SKU</label>
                        <input name="sku" type="text" value="{{ old('sku', $variant->sku) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Código de barras</label>
                        <input name="barcode" type="text" value="{{ old('barcode', $variant->barcode) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Notas</label>
                        <textarea name="notes" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes', $variant->notes) }}</textarea>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 text-sm text-gray-700">
                    <label class="flex items-center gap-2"><input type="checkbox" name="tracks_expiration" value="1" @checked(old('tracks_expiration', $variant->tracks_expiration))> Controla vencimiento</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_returnable" value="1" @checked(old('is_returnable', $variant->is_returnable))> Es retornable</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $variant->exists ? $variant->is_active : true))> Activa</label>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        @if ($variant->exists)
                            <a href="{{ route('products.variants.presentations.index', [$product, $variant]) }}" class="text-sm text-catalog-primary hover:text-catalog-accent">Administrar presentaciones</a>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('products.variants.index', $product) }}" class="text-sm text-gray-600">Cancelar</a>
                        <button class="rounded-md bg-catalog-primary px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
