@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Lotes de inventario</h2>
            <a href="{{ route('opening-inventory.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Volver a inventario</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-4 grid gap-4 md:grid-cols-3">
                <a href="{{ route('opening-inventory.create') }}" class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 hover:ring-emerald-300">
                    <p class="text-sm text-gray-500">Inventario inicial</p>
                    <p class="mt-1 font-semibold text-gray-900">Registrar nueva tanda</p>
                </a>
                <a href="{{ route('opening-inventory.index') }}" class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 hover:ring-emerald-300">
                    <p class="text-sm text-gray-500">Historial</p>
                    <p class="mt-1 font-semibold text-gray-900">Revisar entradas de apertura</p>
                </a>
                <a href="{{ route('reports.index') }}" class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 hover:ring-emerald-300">
                    <p class="text-sm text-gray-500">Reportes</p>
                    <p class="mt-1 font-semibold text-gray-900">Cruzar stock y movimiento</p>
                </a>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Fecha</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Producto</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Variante</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Origen</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Inicial</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Disponible</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Costo</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($lots as $lot)
                            <tr>
                                <td class="px-4 py-3 text-gray-700">{{ optional($lot->received_at)->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-gray-900">{{ $lot->variant->product->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $lot->variant->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $lot->origin_type }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ number_format((float) $lot->initial_quantity, 3, '.', '') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ number_format((float) $lot->available_quantity, 3, '.', '') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ Money::format($lot->unit_cost_final_amount) }}</td>
                                <td class="px-4 py-3">
                                    @if ($lot->status === 'active')
                                        <x-status-badge tone="success">Disponible</x-status-badge>
                                    @else
                                        <x-status-badge tone="neutral">Agotado</x-status-badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">Aún no hay lotes registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
