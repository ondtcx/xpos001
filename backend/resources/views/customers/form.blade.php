<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$customer->exists ? 'Editar cliente' : 'Nuevo cliente'" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="space-y-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @csrf
                @if ($customer->exists)
                    @method('PUT')
                @endif
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $customer->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Teléfono</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone', $customer->phone) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-700">Dirección</label>
                        <input id="address" name="address" type="text" value="{{ old('address', $customer->address) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notas</label>
                        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes', $customer->notes) }}</textarea>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customer->exists ? $customer->is_active : true))>
                    Activo
                </label>
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('customers.index') }}" class="text-sm text-gray-600">Cancelar</a>
                    <button class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
