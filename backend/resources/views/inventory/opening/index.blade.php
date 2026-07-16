@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Inventario inicial" description="Registra y revisa las tandas de inventario inicial cargadas al sistema.">
            <x-slot name="action">
                <div class="flex gap-3">
                    <a href="{{ route('inventory-stock.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Ver stock actual</a>
                    <a href="{{ route('inventory-lots.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Ver lotes</a>
                    <a href="{{ route('opening-inventory.create') }}" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">Registrar inventario inicial</a>
                </div>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            @if (session('opening_inventory_context'))
                @php($context = session('opening_inventory_context'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm">
                    <p class="font-medium text-emerald-900">Último registro creado</p>
                    <p class="mt-1 text-emerald-800">{{ $context['variant_label'] }} · entrada #{{ $context['entry_id'] }} · lote #{{ $context['lot_id'] }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('inventory-lots.index') }}" class="rounded-md border border-emerald-300 bg-white px-3 py-2 text-sm font-medium text-emerald-700">Revisar lotes</a>
                        <a href="{{ route('opening-inventory.create') }}" class="rounded-md border border-emerald-300 bg-white px-3 py-2 text-sm font-medium text-emerald-700">Registrar otra tanda</a>
                    </div>
                </div>
            @endif

            <div class="mb-4 grid gap-4 md:grid-cols-4">
                <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Tandas registradas</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $summary['entries'] }}</p>
                </div>
                <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Auditadas</p>
                    <p class="mt-1 text-xl font-semibold text-emerald-700">{{ $summary['audited'] }}</p>
                </div>
                <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Pendientes de auditoría</p>
                    <p class="mt-1 text-xl font-semibold text-amber-700">{{ $summary['pending_audit'] }}</p>
                </div>
                <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Unidades cargadas</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ number_format((float) $summary['units'], 3, '.', '') }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Fecha</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Producto</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Variante</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Cantidad</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Costo estimado</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Auditado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($entries as $entry)
                            <tr>
                                <td class="px-4 py-3 text-gray-700">{{ optional($entry->recorded_at)->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-gray-900">{{ $entry->variant->product->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $entry->variant->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ number_format((float) $entry->quantity, 3, '.', '') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ Money::format($entry->estimated_unit_cost_amount) }}</td>
                                <td class="px-4 py-3">
                                    @if ($entry->is_audited)
                                        <x-status-badge tone="success">Sí</x-status-badge>
                                    @else
                                        <x-status-badge tone="warning">No</x-status-badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Aún no hay inventario inicial registrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
