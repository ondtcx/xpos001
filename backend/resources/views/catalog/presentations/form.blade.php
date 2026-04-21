<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">{{ $presentation->exists ? 'Editar presentación' : 'Nueva presentación' }} — {{ $variant->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <form method="POST" action="{{ $presentation->exists ? route('products.variants.presentations.update', [$product, $variant, $presentation]) : route('products.variants.presentations.store', [$product, $variant]) }}" class="space-y-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @csrf
                @if ($presentation->exists)
                    @method('PUT')
                @endif

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre</label>
                        <input name="name" type="text" value="{{ old('name', $presentation->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Factor de conversión</label>
                        <input name="conversion_factor" type="number" step="0.001" min="0.001" value="{{ old('conversion_factor', $presentation->conversion_factor) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        @error('conversion_factor') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 text-sm text-gray-700">
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $presentation->is_default))> Predeterminada</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $presentation->exists ? $presentation->is_active : true))> Activa</label>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        @if ($presentation->exists)
                            <a href="{{ route('products.variants.presentations.prices.index', [$product, $variant, $presentation]) }}" class="text-sm text-indigo-600">Ver historial de precios</a>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('products.variants.presentations.index', [$product, $variant]) }}" class="text-sm text-gray-600">Cancelar</a>
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
