<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Marcas" description="Administra las marcas del catálogo de productos.">
            <x-slot name="action">
                <a href="{{ route('brands.create') }}"
                   class="rounded-md bg-catalog-primary px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
                    Nueva marca
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-catalog-border">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($brands as $brand)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $brand->name }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $brand->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $brand->is_active ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('brands.edit', $brand) }}" class="text-catalog-primary hover:text-catalog-accent">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-gray-500">Aún no hay marcas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
