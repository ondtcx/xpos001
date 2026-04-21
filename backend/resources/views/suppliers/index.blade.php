<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Proveedores</h2>
            <a href="{{ route('suppliers.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Nuevo proveedor</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Teléfono</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Identificación</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($suppliers as $supplier)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $supplier->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $supplier->phone ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $supplier->tax_id ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $supplier->is_active ? 'Activo' : 'Inactivo' }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('suppliers.edit', $supplier) }}" class="text-indigo-600 hover:text-indigo-800">Editar</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Aún no hay proveedores registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
