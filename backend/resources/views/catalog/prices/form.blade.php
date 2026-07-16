<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Nuevo precio — {{ $presentation->name }}" description="{{ $product->name }} / {{ $variant->name }}" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('products.variants.presentations.prices.store', [$product, $variant, $presentation]) }}" class="space-y-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-catalog-border">
                @csrf

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Precio de venta</label>
                        <input name="price" type="number" step="0.01" min="0.01" value="{{ old('price') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        @error('price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Precio mínimo</label>
                        <input name="min_price" type="number" step="0.01" min="0" value="{{ old('min_price') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Margen sugerido (%)</label>
                        <input name="suggested_margin_percent" type="number" step="0.01" min="0" value="{{ old('suggested_margin_percent') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Inicio de vigencia</label>
                        <input name="starts_at" type="datetime-local" value="{{ old('starts_at', now()->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        @error('starts_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Motivo</label>
                        <input name="reason" type="text" value="{{ old('reason') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej. Ajuste por aumento de costo">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('products.variants.presentations.prices.index', [$product, $variant, $presentation]) }}" class="text-sm text-gray-600">Cancelar</a>
                    <button class="rounded-md bg-catalog-primary px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">Guardar precio</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
