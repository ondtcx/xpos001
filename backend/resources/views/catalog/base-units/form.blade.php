<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$baseUnit->exists ? 'Editar unidad base' : 'Nueva unidad base'" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <form method="POST" action="{{ $baseUnit->exists ? route('base-units.update', $baseUnit) : route('base-units.store') }}" class="space-y-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-catalog-border">
                @csrf
                @if ($baseUnit->exists)
                    @method('PUT')
                @endif

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $baseUnit->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="symbol" class="block text-sm font-medium text-gray-700">Símbolo</label>
                    <input id="symbol" name="symbol" type="text" value="{{ old('symbol', $baseUnit->symbol) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    @error('symbol') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('base-units.index') }}" class="text-sm text-gray-600">Cancelar</a>
                    <button class="rounded-md bg-catalog-primary px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
