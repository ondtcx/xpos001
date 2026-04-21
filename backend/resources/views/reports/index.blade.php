@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Reportes operativos</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('reports.index') }}" class="grid gap-4 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 md:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Fecha inicio</label>
                    <input name="start_date" type="date" value="{{ $start->toDateString() }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Fecha fin</label>
                    <input name="end_date" type="date" value="{{ $end->toDateString() }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="flex items-end gap-3 md:col-span-2">
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Filtrar</button>
                    <a href="{{ route('reports.export.csv', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Exportar CSV</a>
                    <a href="{{ route('reports.print', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" target="_blank" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Vista imprimible</a>
                </div>
            </form>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200"><p class="text-sm text-gray-500">Ventas</p><p class="mt-2 text-2xl font-semibold text-gray-900">{{ Money::format($salesTodayTotal) }}</p></div>
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200"><p class="text-sm text-gray-500">Utilidad</p><p class="mt-2 text-2xl font-semibold text-gray-900">{{ Money::format($profitToday) }}</p></div>
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200"><p class="text-sm text-gray-500">Compras</p><p class="mt-2 text-2xl font-semibold text-gray-900">{{ Money::format($purchasesTotal) }}</p></div>
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200"><p class="text-sm text-gray-500">Stock total</p><p class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format((float) $stockCurrent, 3, '.', '') }}</p></div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="font-semibold text-gray-900">Fiados pendientes</h3>
                    <p class="mt-2 text-sm text-gray-500">Total pendiente: {{ Money::format($receivablesPendingTotal) }}</p>
                    <div class="mt-4 space-y-3 text-sm">
                        @forelse ($receivablesOpen as $receivable)
                            <div class="rounded-md border border-gray-200 p-3">
                                <p class="font-medium text-gray-900">{{ $receivable->customer->name }}</p>
                                <p class="text-gray-600">Pendiente: {{ Money::format($receivable->pending_amount) }}</p>
                            </div>
                        @empty
                            <p class="text-gray-600">No hay cuentas abiertas.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="font-semibold text-gray-900">Abonos recibidos</h3>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ Money::format($receivedPaymentsTotal) }}</p>
                    <h4 class="mt-6 font-semibold text-gray-900">Cierres de caja</h4>
                    <div class="mt-3 space-y-3 text-sm">
                        @forelse ($cashClosures as $cash)
                            <div class="rounded-md border border-gray-200 p-3">
                                <p class="font-medium text-gray-900">Caja #{{ $cash->id }}</p>
                                <p class="text-gray-600">Diferencia: {{ Money::format($cash->difference_amount ?? 0) }}</p>
                            </div>
                        @empty
                            <p class="text-gray-600">No hay cierres en el período.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="font-semibold text-gray-900">Productos por agotarse</h3>
                    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-gray-500">Producto</th><th class="px-4 py-3 text-left text-gray-500">Variante</th><th class="px-4 py-3 text-left text-gray-500">Disponible</th></tr></thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($lowStock as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-900">{{ $row->variant->product->name }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row->variant->name }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ number_format((float) $row->total_available, 3, '.', '') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">No hay productos críticos en el período.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="font-semibold text-gray-900">Compras por proveedor</h3>
                    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-gray-500">Proveedor</th><th class="px-4 py-3 text-left text-gray-500">Compras</th><th class="px-4 py-3 text-left text-gray-500">Total</th></tr></thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($purchasesBySupplier as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-900">{{ $row->supplier?->name ?? 'Sin proveedor' }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row->purchases_count }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ Money::format((int) $row->total_amount) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">No hay compras en el período.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="font-semibold text-gray-900">Margen por producto</h3>
                    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-gray-500">Producto</th><th class="px-4 py-3 text-left text-gray-500">Ventas</th><th class="px-4 py-3 text-left text-gray-500">Costo</th><th class="px-4 py-3 text-left text-gray-500">Utilidad</th></tr></thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($marginByProduct as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-900">{{ $row->variant->product->name }} — {{ $row->variant->name }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ Money::format((int) $row->total_sales_amount) }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ Money::format((int) $row->total_cost_amount) }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ Money::format((int) $row->total_profit_amount) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No hay ventas en el período.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="font-semibold text-gray-900">Resumen de caja por método</h3>
                    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-gray-500">Método</th><th class="px-4 py-3 text-left text-gray-500">Movimiento</th><th class="px-4 py-3 text-left text-gray-500">Total</th></tr></thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($cashSummary as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-900">{{ $row->payment_method ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row->movement_type }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ Money::format((int) $row->total_amount) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">No hay movimientos en el período.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h3 class="font-semibold text-gray-900">Movimiento por lote</h3>
                <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-gray-500">Producto</th><th class="px-4 py-3 text-left text-gray-500">Variante</th><th class="px-4 py-3 text-left text-gray-500">Origen</th><th class="px-4 py-3 text-left text-gray-500">Disponible</th><th class="px-4 py-3 text-left text-gray-500">Último movimiento</th></tr></thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($lotMovements as $lot)
                                @php($lastMovement = $lot->movements->first())
                                <tr>
                                    <td class="px-4 py-3 text-gray-900">{{ $lot->variant->product->name }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $lot->variant->name }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $lot->origin_type }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ number_format((float) $lot->available_quantity, 3, '.', '') }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $lastMovement?->movement_type ?? 'Sin movimientos' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay lotes para mostrar.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
