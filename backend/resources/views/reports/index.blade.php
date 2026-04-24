@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Reportes operativos</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('sales.index') }}" class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300">
                    <p class="text-sm text-gray-500">Ventas</p>
                    <p class="mt-1 font-semibold text-gray-900">Ir al listado y revisar detalles</p>
                </a>
                <a href="{{ route('purchases.index') }}" class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300">
                    <p class="text-sm text-gray-500">Compras</p>
                    <p class="mt-1 font-semibold text-gray-900">Auditar compras y lotes creados</p>
                </a>
                <a href="{{ route('receivables.index') }}" class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300">
                    <p class="text-sm text-gray-500">Fiados</p>
                    <p class="mt-1 font-semibold text-gray-900">Abrir cuentas por cobrar</p>
                </a>
                <a href="{{ route('inventory-lots.index') }}" class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300">
                    <p class="text-sm text-gray-500">Lotes</p>
                    <p class="mt-1 font-semibold text-gray-900">Ver disponibilidad y trazabilidad</p>
                </a>
            </div>

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
                    <a href="{{ route('reports.export.csv', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">CSV ejecutivo</a>
                    <a href="{{ route('reports.print', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" target="_blank" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Vista imprimible</a>
                </div>
            </form>

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">Exportaciones operativas</h3>
                        <p class="mt-1 text-sm text-gray-500">CSV sigue disponible para trabajo técnico rápido. Excel ya sale con múltiples hojas por dominio para ventas, compras y cobranza.</p>
                    </div>
                    <div class="grid gap-3 md:grid-cols-9">
                        <a href="{{ route('reports.export.sales-xlsx', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700">Excel ventas</a>
                        <a href="{{ route('reports.export.purchases-xlsx', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700">Excel compras</a>
                        <a href="{{ route('reports.export.receivables-xlsx', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700">Excel cobranza</a>
                        <a href="{{ route('reports.export.sales-pdf', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700">PDF ventas</a>
                        <a href="{{ route('reports.export.purchases-pdf', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700">PDF compras</a>
                        <a href="{{ route('reports.export.receivables-pdf', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700">PDF cobranza</a>
                        <a href="{{ route('reports.export.csv', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Resumen ejecutivo</a>
                        <a href="{{ route('reports.export.sales-summary-csv', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Ventas por cabecera</a>
                        <a href="{{ route('reports.export.sales-lines-csv', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Ventas por línea</a>
                        <a href="{{ route('reports.export.purchases-summary-csv', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Compras por cabecera</a>
                        <a href="{{ route('reports.export.purchases-lines-csv', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Compras por línea</a>
                        <a href="{{ route('reports.export.receivables-csv', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Fiados por cuenta</a>
                        <a href="{{ route('reports.export.receivable-payments-csv', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Abonos de fiado</a>
                        <a href="{{ route('reports.export.lots-csv', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Lotes actuales</a>
                        <a href="{{ route('reports.export.lot-movements-csv', ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Movimientos por lote</a>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Ventas netas</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ Money::format($salesNetTotal) }}</p>
                    <p class="mt-2 text-xs text-gray-500">Bruto {{ Money::format($salesGrossTotal) }} · anuladas {{ Money::format($salesVoidedTotal) }}</p>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Utilidad confiable</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ Money::format($profitReliableTotal) }}</p>
                    <p class="mt-2 text-xs text-gray-500">Excluida por warnings {{ Money::format($profitWarningTotal) }} · anulada {{ Money::format($profitVoidedTotal) }}</p>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm text-gray-500">Compras netas</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ Money::format($purchasesNetTotal) }}</p>
                    <p class="mt-2 text-xs text-gray-500">Bruto {{ Money::format($purchasesGrossTotal) }} · anuladas {{ Money::format($purchasesVoidedTotal) }}</p>
                </div>
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200"><p class="text-sm text-gray-500">Stock total</p><p class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format((float) $stockCurrent, 3, '.', '') }}</p></div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="font-semibold text-gray-900">Ventas del período</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Brutas</dt><dd class="font-medium text-gray-900">{{ Money::format($salesGrossTotal) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Anuladas</dt><dd class="font-medium text-red-700">{{ Money::format($salesVoidedTotal) }}</dd></div>
                        <div class="flex items-center justify-between gap-4 border-t border-gray-100 pt-3"><dt class="text-gray-900 font-medium">Netas</dt><dd class="font-semibold text-gray-900">{{ Money::format($salesNetTotal) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Cobrado bruto</dt><dd class="font-medium text-gray-900">{{ Money::format($salesGrossPaid) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Cobrado anulado</dt><dd class="font-medium text-red-700">{{ Money::format($salesVoidedPaid) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Cobrado neto</dt><dd class="font-medium text-gray-900">{{ Money::format($salesNetPaid) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Fiado bruto</dt><dd class="font-medium text-gray-900">{{ Money::format($salesGrossCredit) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Fiado anulado</dt><dd class="font-medium text-red-700">{{ Money::format($salesVoidedCredit) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Fiado neto</dt><dd class="font-medium text-gray-900">{{ Money::format($salesNetCredit) }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="font-semibold text-gray-900">Utilidad y margen</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Utilidad confiable</dt><dd class="font-medium text-gray-900">{{ Money::format($profitReliableTotal) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Excluida por warnings</dt><dd class="font-medium text-amber-700">{{ Money::format($profitWarningTotal) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Utilidad anulada</dt><dd class="font-medium text-red-700">{{ Money::format($profitVoidedTotal) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Margen excluido por warnings</dt><dd class="font-medium text-amber-700">{{ Money::format((int) $marginExcludedByWarnings) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Margen anulado</dt><dd class="font-medium text-red-700">{{ Money::format((int) $marginVoided) }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="font-semibold text-gray-900">Compras y cobranza</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Compras brutas</dt><dd class="font-medium text-gray-900">{{ Money::format($purchasesGrossTotal) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Compras anuladas</dt><dd class="font-medium text-red-700">{{ Money::format($purchasesVoidedTotal) }}</dd></div>
                        <div class="flex items-center justify-between gap-4 border-t border-gray-100 pt-3"><dt class="text-gray-900 font-medium">Compras netas</dt><dd class="font-semibold text-gray-900">{{ Money::format($purchasesNetTotal) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Abonos brutos</dt><dd class="font-medium text-gray-900">{{ Money::format($receivedPaymentsGrossTotal) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Abonos revertidos</dt><dd class="font-medium text-red-700">{{ Money::format($receivedPaymentsReversedTotal) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Abonos netos</dt><dd class="font-medium text-gray-900">{{ Money::format($receivedPaymentsNetTotal) }}</dd></div>
                        <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Fiados pendientes</dt><dd class="font-medium text-gray-900">{{ Money::format($receivablesPendingTotal) }}</dd></div>
                    </dl>
                </div>
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
                                <a href="{{ route('receivables.show', $receivable) }}" class="mt-2 inline-flex text-xs font-medium text-indigo-700 hover:text-indigo-900">Ver detalle</a>
                            </div>
                        @empty
                            <p class="text-gray-600">No hay cuentas abiertas.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="font-semibold text-gray-900">Abonos recibidos</h3>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ Money::format($receivedPaymentsNetTotal) }}</p>
                    <p class="mt-2 text-sm text-gray-500">Brutos {{ Money::format($receivedPaymentsGrossTotal) }} · revertidos {{ Money::format($receivedPaymentsReversedTotal) }}</p>
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
                                        <td class="px-4 py-3 text-gray-700">
                                            <div>{{ number_format((float) $row->total_available, 3, '.', '') }}</div>
                                            <a href="{{ route('inventory-lots.index') }}" class="mt-1 inline-flex text-xs font-medium text-indigo-700 hover:text-indigo-900">Ver lotes</a>
                                        </td>
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
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-semibold text-gray-900">Ventas recientes del período</h3>
                        <a href="{{ route('sales.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">Ver todas</a>
                    </div>
                    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-gray-500">Fecha</th><th class="px-4 py-3 text-left text-gray-500">Cliente</th><th class="px-4 py-3 text-left text-gray-500">Estado</th><th class="px-4 py-3 text-left text-gray-500">Total</th><th class="px-4 py-3 text-right text-gray-500">Acción</th></tr></thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($recentSales as $sale)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-700">{{ optional($sale->sold_at)->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-3 text-gray-900">{{ $sale->customer?->name ?? 'Venta anónima' }}</td>
                                        <td class="px-4 py-3 text-gray-700">
                                            @if ($sale->isVoided())
                                                <x-status-badge tone="danger">Anulada</x-status-badge>
                                            @elseif ($sale->credit_amount > 0)
                                                <x-status-badge tone="warning">Con saldo pendiente</x-status-badge>
                                            @else
                                                <x-status-badge tone="success">Cobrada</x-status-badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">{{ Money::format($sale->total_amount) }}</td>
                                        <td class="px-4 py-3 text-right"><a href="{{ route('sales.show', $sale) }}" class="text-indigo-700 hover:text-indigo-900">Ver detalle</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay ventas en el período.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-semibold text-gray-900">Compras recientes del período</h3>
                        <a href="{{ route('purchases.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">Ver todas</a>
                    </div>
                    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-gray-500">Fecha</th><th class="px-4 py-3 text-left text-gray-500">Proveedor</th><th class="px-4 py-3 text-left text-gray-500">Estado</th><th class="px-4 py-3 text-left text-gray-500">Total</th><th class="px-4 py-3 text-right text-gray-500">Acción</th></tr></thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($recentPurchases as $purchase)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-700">{{ optional($purchase->purchased_at)->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-3 text-gray-900">{{ $purchase->supplier?->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-700">
                                            @if ($purchase->isVoided())
                                                <x-status-badge tone="danger">Anulada</x-status-badge>
                                            @else
                                                <x-status-badge tone="success">Confirmada</x-status-badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">{{ Money::format($purchase->total_amount) }}</td>
                                        <td class="px-4 py-3 text-right"><a href="{{ route('purchases.show', $purchase) }}" class="text-indigo-700 hover:text-indigo-900">Ver detalle</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay compras en el período.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

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
                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-md bg-slate-50 p-3 text-sm">
                            <p class="text-gray-500">Operativa</p>
                            <p class="mt-1 font-semibold text-gray-900">{{ Money::format($cashOperationalTotal) }}</p>
                        </div>
                        <div class="rounded-md bg-red-50 p-3 text-sm">
                            <p class="text-red-600">Reversas</p>
                            <p class="mt-1 font-semibold text-red-700">{{ Money::format($cashReversalTotal) }}</p>
                        </div>
                        <div class="rounded-md bg-amber-50 p-3 text-sm">
                            <p class="text-amber-700">Manual / otros</p>
                            <p class="mt-1 font-semibold text-amber-800">{{ Money::format($cashManualTotal) }}</p>
                        </div>
                        <div class="rounded-md bg-emerald-50 p-3 text-sm">
                            <p class="text-emerald-700">Neto final</p>
                            <p class="mt-1 font-semibold text-emerald-800">{{ Money::format($cashNetTotal) }}</p>
                        </div>
                    </div>
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
