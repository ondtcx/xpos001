<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Proveedores" description="Administra los proveedores registrados y su información de contacto.">
            <x-slot name="action">
                <a href="{{ route('suppliers.create') }}" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">Nuevo proveedor</a>
            </x-slot>
        </x-page-header>
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
                                <td class="px-4 py-3">
                                    @if ($supplier->is_active)
                                        <x-status-badge tone="success">Activo</x-status-badge>
                                    @else
                                        <x-status-badge tone="neutral">Inactivo</x-status-badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('suppliers.edit', $supplier) }}" class="text-emerald-700 hover:text-emerald-900">Editar</a></td>
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
