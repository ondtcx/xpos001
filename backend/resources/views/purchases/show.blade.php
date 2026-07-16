@php use App\Models\PurchaseItem; use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Detalle de compra #{{ $purchase->id }}</h2>
                <p class="text-sm text-gray-500">Auditoría de cabecera, líneas, lotes creados y consumo posterior.</p>
            </div>
            <a href="{{ route('purchases.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Volver</a>
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
                            <h3 class="font-semibold text-gray-900">Resumen de la compra</h3>
                            <p class="mt-1 text-sm text-gray-500">Fecha {{ optional($purchase->purchased_at)->format('Y-m-d H:i') }} · usuario {{ $purchase->creator?->name ?? '—' }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($purchase->isDetailed())
                                <x-status-badge tone="info">Detallada</x-status-badge>
                            @else
                                <x-status-badge tone="neutral">Rápida</x-status-badge>
                            @endif

                            @if ($purchase->isVoided())
                                <x-status-badge tone="danger">Anulada</x-status-badge>
                            @else
                                <x-status-badge tone="success">Confirmada</x-status-badge>
                            @endif
                        </div>
                    </div>

                    <dl class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4 text-sm">
                        <div><dt class="text-gray-500">Proveedor</dt><dd class="font-medium text-gray-900">{{ $purchase->supplier?->name ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500">Factura</dt><dd class="font-medium text-gray-900">{{ $purchase->invoice_number ?: '—' }}</dd></div>
                        <div><dt class="text-gray-500">Pago</dt><dd class="font-medium text-gray-900">{{ $purchase->payment_type }}</dd></div>
                        <div><dt class="text-gray-500">Lotes creados</dt><dd class="font-medium text-gray-900">{{ $purchase->lots->count() }}</dd></div>
                        <div><dt class="text-gray-500">Subtotal</dt><dd class="font-medium text-gray-900">{{ Money::format($purchase->subtotal_amount) }}</dd></div>
                        <div><dt class="text-gray-500">Descuento global</dt><dd class="font-medium text-gray-900">{{ Money::format($purchase->global_discount_amount) }}</dd></div>
                        <div><dt class="text-gray-500">Impuestos globales</dt><dd class="font-medium text-gray-900">{{ Money::format(($purchase->global_tax_iva_amount ?? 0) + ($purchase->global_tax_ice_amount ?? 0) + ($purchase->global_tax_other_amount ?? 0)) }}</dd></div>
                        <div><dt class="text-gray-500">Costos extra</dt><dd class="font-medium text-gray-900">{{ Money::format($purchase->extra_costs_amount) }}</dd></div>
                        <div><dt class="text-gray-500">Total</dt><dd class="font-medium text-gray-900">{{ Money::format($purchase->total_amount) }}</dd></div>
                        <div><dt class="text-gray-500">Consumo posterior</dt><dd class="font-medium text-gray-900">{{ $hasConsumedLots ? 'Sí' : 'No' }}</dd></div>
                    </dl>

                    @if ($purchase->notes)
                        <div class="mt-6 rounded-md bg-slate-50 p-4 text-sm text-gray-700">
                            <p class="font-medium text-gray-900">Notas operativas</p>
                            <p class="mt-1">{{ $purchase->notes }}</p>
                        </div>
                    @endif

                    @if ($purchase->isVoided())
                        <div class="mt-6 rounded-md bg-red-50 p-4 text-sm text-red-800">
                            <p class="font-medium">Motivo de anulación</p>
                            <p class="mt-1">{{ $purchase->void_reason }}</p>
                            <p class="mt-2 text-xs text-red-700">Anulada por {{ $purchase->voider?->name ?? '—' }} el {{ optional($purchase->voided_at)->format('Y-m-d H:i') }}</p>
                        </div>
                    @endif
                </div>

                <div class="space-y-6">
                    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h3 class="font-semibold text-gray-900">Bloqueos operativos</h3>
                        <div class="mt-4 space-y-3 text-sm">
                            <div class="rounded-md border border-gray-200 p-3">
                                <p class="font-medium text-gray-900">Edición</p>
                                <p class="mt-1 text-gray-600">
                                    @if ($purchase->isDetailed() && $purchase->isConfirmed() && ! $hasConsumedLots)
                                        Disponible mientras ningún lote haya sido consumido.
                                    @elseif ($purchase->isDetailed())
                                        Bloqueada por consumo o estado de la compra.
                                    @else
                                        La compra rápida no usa edición detallada.
                                    @endif
                                </p>
                            </div>
                            <div class="rounded-md border border-gray-200 p-3">
                                <p class="font-medium text-gray-900">Anulación</p>
                                <p class="mt-1 text-gray-600">
                                    @if ($purchase->isConfirmed() && ! $hasConsumedLots)
                                        Disponible mientras ningún lote haya sido consumido.
                                    @else
                                        Bloqueada por consumo o porque la compra ya fue anulada.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h3 class="font-semibold text-gray-900">Distribución global</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">IVA global</dt><dd class="font-medium text-gray-900">{{ Money::format($purchase->global_tax_iva_amount ?? 0) }}</dd></div>
                            <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">ICE global</dt><dd class="font-medium text-gray-900">{{ Money::format($purchase->global_tax_ice_amount ?? 0) }}</dd></div>
                            <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Otro impuesto global</dt><dd class="font-medium text-gray-900">{{ Money::format($purchase->global_tax_other_amount ?? 0) }}</dd></div>
                            <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Descuento global</dt><dd class="font-medium text-gray-900">{{ Money::format($purchase->global_discount_amount) }}</dd></div>
                            <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Costos extra</dt><dd class="font-medium text-gray-900">{{ Money::format($purchase->extra_costs_amount) }}</dd></div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h3 class="font-semibold text-gray-900">Líneas de compra</h3>
                <p class="mt-1 text-sm text-gray-500">Se muestran costos base/finales, descuentos, impuestos y bonificaciones por línea.</p>

                <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Línea</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Recibido</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Costo base</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Ajustes</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Costo final</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Vencimiento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($purchase->items as $item)
                                <tr>
                                    <td class="px-4 py-3 align-top">
                                        <p class="font-medium text-gray-900">{{ $item->variant?->product?->name }} — {{ $item->variant?->name }}</p>
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ $item->line_type === PurchaseItem::LINE_TYPE_BONUS ? 'Bonificación' : 'Normal' }}
                                        </p>
                                        @if ($item->notes)
                                            <p class="mt-1 text-xs text-gray-600">{{ $item->notes }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top text-gray-700">
                                        <p>Base: {{ number_format((float) $item->quantity, 3, '.', '') }}</p>
                                        <p>Bono: {{ number_format((float) $item->bonus_quantity, 3, '.', '') }}</p>
                                        <p class="font-medium text-gray-900">Total: {{ $item->receivedQuantity() }}</p>
                                    </td>
                                    <td class="px-4 py-3 align-top text-gray-700">
                                        <p>Unitario: {{ Money::format($item->unit_cost_base_amount ?? 0) }}</p>
                                        <p>Subtotal: {{ Money::format($item->line_subtotal_amount ?? 0) }}</p>
                                    </td>
                                    <td class="px-4 py-3 align-top text-xs text-gray-600">
                                        <p>Desc línea: {{ Money::format($item->line_discount_amount ?? 0) }}</p>
                                        <p>IVA línea: {{ Money::format(($item->tax_iva_amount ?? 0) + ($item->tax_vat_amount ?? 0)) }}</p>
                                        <p>ICE línea: {{ Money::format(($item->tax_ice_amount ?? 0) + ($item->tax_fixed_amount ?? 0)) }}</p>
                                        <p>Otro línea: {{ Money::format($item->tax_other_amount ?? 0) }}</p>
                                        <p class="mt-2">Desc global: {{ Money::format($item->allocated_global_discount_amount ?? 0) }}</p>
                                        <p>IVA global: {{ Money::format($item->allocated_global_tax_iva_amount ?? 0) }}</p>
                                        <p>ICE global: {{ Money::format($item->allocated_global_tax_ice_amount ?? 0) }}</p>
                                        <p>Otro global: {{ Money::format($item->allocated_global_tax_other_amount ?? 0) }}</p>
                                        <p>Extras: {{ Money::format($item->allocated_extra_costs_amount ?? 0) }}</p>
                                    </td>
                                    <td class="px-4 py-3 align-top text-gray-700">
                                        <p>Total línea: {{ Money::format($item->total_cost_amount ?? 0) }}</p>
                                        <p>Unitario final: {{ Money::format($item->unit_cost_final_amount ?? 0) }}</p>
                                    </td>
                                    <td class="px-4 py-3 align-top text-gray-700">{{ optional($item->expiration_date)->format('Y-m-d') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h3 class="font-semibold text-gray-900">Lotes creados</h3>
                <p class="mt-1 text-sm text-gray-500">Incluye disponibilidad actual y movimientos de consumo posteriores si existieron.</p>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    @forelse ($purchase->lots as $lot)
                        <div class="rounded-lg border border-gray-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium text-gray-900">Lote #{{ $lot->id }}</p>
                                    <p class="text-sm text-gray-600">{{ $lot->variant?->product?->name }} — {{ $lot->variant?->name }}</p>
                                </div>
                                @if ($lot->status === 'active')
                                    <x-status-badge tone="success">Disponible</x-status-badge>
                                @else
                                    <x-status-badge tone="neutral">Agotado</x-status-badge>
                                @endif
                            </div>

                            <dl class="mt-4 grid gap-3 md:grid-cols-2 text-sm">
                                <div><dt class="text-gray-500">Recibido</dt><dd class="font-medium text-gray-900">{{ number_format((float) $lot->initial_quantity, 3, '.', '') }}</dd></div>
                                <div><dt class="text-gray-500">Disponible</dt><dd class="font-medium text-gray-900">{{ number_format((float) $lot->available_quantity, 3, '.', '') }}</dd></div>
                                <div><dt class="text-gray-500">Bono</dt><dd class="font-medium text-gray-900">{{ number_format((float) $lot->bonus_quantity, 3, '.', '') }}</dd></div>
                                <div><dt class="text-gray-500">Costo final</dt><dd class="font-medium text-gray-900">{{ Money::format($lot->unit_cost_final_amount) }}</dd></div>
                                <div><dt class="text-gray-500">Recibido en</dt><dd class="font-medium text-gray-900">{{ optional($lot->received_at)->format('Y-m-d H:i') }}</dd></div>
                                <div><dt class="text-gray-500">Vencimiento</dt><dd class="font-medium text-gray-900">{{ optional($lot->expiration_date)->format('Y-m-d') ?? '—' }}</dd></div>
                            </dl>

                            <div class="mt-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Consumo posterior</p>
                                <div class="mt-2 space-y-2 text-sm">
                                    @forelse (($consumptionMovements[$lot->id] ?? collect()) as $movement)
                                        <div class="rounded-md bg-amber-50 p-3 text-amber-900">
                                            <p class="font-medium">{{ $movement->movement_type }}</p>
                                            <p class="mt-1">Cantidad: {{ number_format((float) $movement->quantity, 3, '.', '') }}</p>
                                            <p>Referencia: {{ $movement->reference_type }} #{{ $movement->reference_id }}</p>
                                            <p>Fecha: {{ optional($movement->movement_at)->format('Y-m-d H:i') }}</p>
                                        </div>
                                    @empty
                                        <p class="text-gray-500">Sin consumos posteriores registrados.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Esta compra no tiene lotes registrados.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
