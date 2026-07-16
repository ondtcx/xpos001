<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Stock actual" description="Consulta el stock disponible por producto y variante, incluyendo los que están en cero.">
            <x-slot name="action">
                <div class="flex gap-3">
                    <a href="{{ route('inventory-lots.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Ver lotes</a>
                    <a href="{{ route('opening-inventory.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Inventario inicial</a>
                </div>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-4 grid gap-4 md:grid-cols-3">
                <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Variantes activas</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $summary['total_variants'] }}</p>
                </div>
                <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Stock bajo (≤ 5)</p>
                    <p class="mt-1 text-xl font-semibold text-amber-700">{{ $summary['low_stock'] }}</p>
                </div>
                <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Agotadas</p>
                    <p class="mt-1 text-xl font-semibold text-red-700">{{ $summary['out_of_stock'] }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Producto</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Variante</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Unidad</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Stock disponible</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($variants as $variant)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $variant->product->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $variant->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $variant->baseUnit->symbol ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ number_format($variant->available_quantity, 3, '.', '') }}</td>
                                <td class="px-4 py-3">
                                    @if ($variant->available_quantity <= 0)
                                        <x-status-badge tone="danger">Agotado</x-status-badge>
                                    @elseif ($variant->available_quantity <= 5)
                                        <x-status-badge tone="warning">Stock bajo</x-status-badge>
                                    @else
                                        <x-status-badge tone="success">Disponible</x-status-badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay variantes activas registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
