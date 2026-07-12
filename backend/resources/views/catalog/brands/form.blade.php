<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$brand->exists ? 'Editar marca' : 'Nueva marca'" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <form method="POST" action="{{ $brand->exists ? route('brands.update', $brand) : route('brands.store') }}" class="space-y-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-border">
                @csrf
                @if ($brand->exists)
                    @method('PUT')
                @endif

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $brand->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $brand->is_active))>
                    Activa
                </label>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('brands.index') }}" class="text-sm text-gray-600">Cancelar</a>
                    <button class="rounded-md bg-catalog-primary px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
