@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Detalle de venta #{{ $sale->id }}</h2>
                <p class="text-sm text-gray-500">Auditoría operativa de líneas, cobros, fiado y consumos por lote.</p>
            </div>
            <a href="{{ route('sales.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Volver</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-2">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-gray-900">Resumen de la venta</h3>
                            <p class="mt-1 text-sm text-gray-500">Fecha {{ optional($sale->sold_at)->format('Y-m-d H:i') }} · usuario {{ $sale->creator?->name ?? '—' }}</p>
                        </div>
                        <div>
                            @if ($sale->isVoided())
                                <x-status-badge tone="danger">Anulada</x-status-badge>
                            @elseif ($sale->credit_amount > 0)
                                <x-status-badge tone="warning">Con saldo pendiente</x-status-badge>
                            @else
                                <x-status-badge tone="success">Cobrada</x-status-badge>
                            @endif
                        </div>
                    </div>

                    <dl class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4 text-sm">
                        <div><dt class="text-gray-500">Cliente</dt><dd class="font-medium text-gray-900">{{ $sale->customer?->name ?? 'Venta anónima' }}</dd></div>
                        <div><dt class="text-gray-500">Caja origen</dt><dd class="font-medium text-gray-900">{{ $sale->cashSession?->id ? 'Caja #' . $sale->cashSession->id : '—' }}</dd></div>
                        <div><dt class="text-gray-500">Total</dt><dd class="font-medium text-gray-900">{{ Money::format($sale->total_amount) }}</dd></div>
                        <div><dt class="text-gray-500">Pagado</dt><dd class="font-medium text-gray-900">{{ Money::format($sale->paid_amount) }}</dd></div>
                        <div><dt class="text-gray-500">Fiado</dt><dd class="font-medium text-gray-900">{{ Money::format($sale->credit_amount) }}</dd></div>
                        <div><dt class="text-gray-500">Utilidad registrada</dt><dd class="font-medium text-gray-900">{{ Money::format((int) $sale->items->sum('total_profit_amount')) }}</dd></div>
                        <div><dt class="text-gray-500">Warnings en líneas</dt><dd class="font-medium text-gray-900">{{ $sale->items->where('has_stock_warning', true)->count() + $sale->items->where('has_cost_warning', true)->count() }}</dd></div>
                        <div><dt class="text-gray-500">Overrides de precio</dt><dd class="font-medium text-gray-900">{{ $sale->items->where('has_manual_price_override', true)->count() }}</dd></div>
                    </dl>

                    @if ($sale->notes)
                        <div class="mt-6 rounded-md bg-slate-50 p-4 text-sm text-gray-700">
                            <p class="font-medium text-gray-900">Notas operativas</p>
                            <p class="mt-1">{{ $sale->notes }}</p>
                        </div>
                    @endif

                    @if ($sale->isVoided())
                        <div class="mt-6 rounded-md bg-red-50 p-4 text-sm text-red-800">
                            <p class="font-medium">Motivo de anulación</p>
                            <p class="mt-1">{{ $sale->void_reason }}</p>
                            <p class="mt-2 text-xs text-red-700">Anulada por {{ $sale->voider?->name ?? '—' }} el {{ optional($sale->voided_at)->format('Y-m-d H:i') }}</p>
                        </div>
                    @endif
                </div>

                <div class="space-y-6">
                    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h3 class="font-semibold text-gray-900">Cobros de la venta</h3>
                        <div class="mt-4 space-y-3 text-sm">
                            @forelse ($sale->payments as $payment)
                                <div class="rounded-md border border-gray-200 p-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="font-medium text-gray-900">{{ strtoupper($payment->payment_method) }}</p>
                                        <p class="font-medium text-gray-900">{{ Money::format($payment->amount) }}</p>
                                    </div>
                                    <p class="mt-1 text-gray-500">{{ optional($payment->received_at)->format('Y-m-d H:i') }}</p>
                                    @if ($payment->isReversed())
                                        <p class="mt-2 rounded-md bg-red-50 px-2 py-1 text-xs text-red-700">Revertido · {{ $payment->reversal_reason }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-gray-500">No hubo pagos directos en esta venta.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h3 class="font-semibold text-gray-900">Cuenta por cobrar</h3>
                        @if ($receivable)
                            <dl class="mt-4 space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Original</dt><dd class="font-medium text-gray-900">{{ Money::format($receivable->original_amount) }}</dd></div>
                                <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Pendiente</dt><dd class="font-medium text-gray-900">{{ Money::format($receivable->pending_amount) }}</dd></div>
                                <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Estado</dt><dd class="font-medium text-gray-900">{{ $receivable->status }}</dd></div>
                            </dl>

                            @if ($receivable->isCancelled())
                                <div class="mt-4 rounded-md bg-red-50 p-3 text-xs text-red-700">
                                    Cancelada por anulación de venta.
                                </div>
                            @endif

                            <div class="mt-4 space-y-3 text-sm">
                                @forelse ($receivable->payments as $payment)
                                    <div class="rounded-md border border-gray-200 p-3">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="font-medium text-gray-900">{{ strtoupper($payment->payment_method) }}</p>
                                            <p class="font-medium text-gray-900">{{ Money::format($payment->amount) }}</p>
                                        </div>
                                        <p class="mt-1 text-gray-500">{{ optional($payment->paid_at)->format('Y-m-d H:i') }} · {{ $payment->creator?->name ?? '—' }}</p>
                                        @if ($payment->isReversed())
                                            <p class="mt-2 rounded-md bg-red-50 px-2 py-1 text-xs text-red-700">Revertido · {{ $payment->reversal_reason }}</p>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-gray-500">No hay abonos registrados.</p>
                                @endforelse
                            </div>
                        @else
                            <p class="mt-4 text-sm text-gray-500">Esta venta no generó cuenta por cobrar.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-gray-900">Líneas vendidas</h3>
                        <p class="mt-1 text-sm text-gray-500">Incluye override de precio, warnings y consumo FIFO por lote.</p>
                    </div>
                </div>

                <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Línea</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Cantidad</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Precio original</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Precio aplicado</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Costo</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Utilidad</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Señales</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($sale->items as $item)
                                <tr>
                                    <td class="px-4 py-3 align-top">
                                        <p class="font-medium text-gray-900">{{ $item->description_snapshot }}</p>
                                        @if ($item->has_manual_price_override && $item->manual_price_reason)
                                            <p class="mt-1 text-xs text-emerald-700">Motivo override: {{ $item->manual_price_reason }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top text-gray-700">{{ number_format((float) $item->quantity, 3, '.', '') }}</td>
                                    <td class="px-4 py-3 align-top text-gray-700">{{ Money::format($item->original_unit_price_amount ?? $item->unit_price_amount) }}</td>
                                    <td class="px-4 py-3 align-top text-gray-900">{{ Money::format($item->unit_price_amount) }}</td>
                                    <td class="px-4 py-3 align-top text-gray-700">{{ Money::format((int) $item->total_cost_amount) }}</td>
                                    <td class="px-4 py-3 align-top text-gray-700">{{ Money::format((int) $item->total_profit_amount) }}</td>
                                    <td class="px-4 py-3 align-top text-xs text-gray-600">
                                        <div class="flex flex-wrap gap-2">
                                            @if ($item->has_manual_price_override)
                                                <x-signal-badge tone="info">Override</x-signal-badge>
                                            @endif
                                            @if ($item->has_stock_warning)
                                                <x-signal-badge tone="warning">Stock</x-signal-badge>
                                            @endif
                                            @if ($item->has_cost_warning)
                                                <x-signal-badge tone="cost">Costo</x-signal-badge>
                                            @endif
                                            @if (! $item->has_manual_price_override && ! $item->has_stock_warning && ! $item->has_cost_warning)
                                                <span class="text-gray-400">Sin novedades</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-slate-50/60">
                                    <td colspan="7" class="px-4 py-3">
                                        <div>
                                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Consumos por lote</p>
                                            <div class="mt-2 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                                @forelse ($item->lotConsumptions as $consumption)
                                                    <div class="rounded-md border border-slate-200 bg-white p-3 text-sm">
                                                        <p class="font-medium text-gray-900">Lote #{{ $consumption->lot_id }}</p>
                                                        <p class="mt-1 text-gray-600">Consumido: {{ number_format((float) $consumption->quantity, 3, '.', '') }}</p>
                                                        <p class="text-gray-600">Costo unitario: {{ Money::format($consumption->unit_cost_amount) }}</p>
                                                        <p class="text-gray-600">Costo total: {{ Money::format($consumption->total_cost_amount) }}</p>
                                                        <p class="text-gray-500">Disponible actual: {{ number_format((float) ($consumption->lot?->available_quantity ?? 0), 3, '.', '') }}</p>
                                                    </div>
                                                @empty
                                                    <p class="text-sm text-gray-500">No hay consumos por lote registrados para esta línea.</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
