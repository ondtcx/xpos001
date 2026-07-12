@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Caja</h2>
            @if ($currentSession)
                <a href="{{ route('cash.close-form', $currentSession) }}" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white">Cerrar caja actual</a>
            @else
                <a href="{{ route('cash.create') }}" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Abrir caja</a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">Caja consolidada</h3>
                        <p class="mt-1 text-sm text-gray-600">Resumen histórico por fecha de cierre.</p>
                    </div>

                    <form method="GET" action="{{ route('cash.index') }}" class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Desde</label>
                            <input name="start_date" type="date" value="{{ $cashRange['start_date'] }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hasta</label>
                            <input name="end_date" type="date" value="{{ $cashRange['end_date'] }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div class="flex items-end gap-3">
                            <button class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Filtrar</button>
                            <a href="{{ route('cash.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Restablecer</a>
                        </div>
                    </form>
                </div>

                <dl class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5 text-sm">
                    <div class="rounded-md bg-gray-50 p-4">
                        <dt class="text-gray-500">Cierres del período</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $cashSummary['closed_sessions_count'] }}</dd>
                    </div>
                    <div class="rounded-md bg-gray-50 p-4">
                        <dt class="text-gray-500">Efectivo esperado</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">{{ Money::format($cashSummary['expected_cash_amount']) }}</dd>
                    </div>
                    <div class="rounded-md bg-gray-50 p-4">
                        <dt class="text-gray-500">Efectivo contado</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">{{ Money::format($cashSummary['counted_cash_amount']) }}</dd>
                    </div>
                    <div class="rounded-md bg-gray-50 p-4">
                        <dt class="text-gray-500">Transferencias esperadas</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">{{ Money::format($cashSummary['expected_transfer_amount']) }}</dd>
                    </div>
                    <div class="rounded-md bg-gray-50 p-4">
                        <dt class="text-gray-500">Diferencia neta</dt>
                        <dd @class([
                            'mt-1 text-lg font-semibold',
                            'text-emerald-700' => $cashSummary['difference_amount'] > 0,
                            'text-red-700' => $cashSummary['difference_amount'] < 0,
                            'text-gray-900' => $cashSummary['difference_amount'] === 0,
                        ])>{{ Money::format($cashSummary['difference_amount']) }}</dd>
                    </div>
                </dl>

                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-6 text-sm">
                    <div class="rounded-md border border-gray-200 p-4">
                        <dt class="text-gray-500">Cierres exactos</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $cashSummary['balanced_sessions_count'] }}</dd>
                    </div>
                    <div class="rounded-md border border-red-200 bg-red-50 p-4">
                        <dt class="text-red-700">Cierres con faltante</dt>
                        <dd class="mt-1 text-lg font-semibold text-red-700">{{ $cashSummary['shortage_sessions_count'] }}</dd>
                    </div>
                    <div class="rounded-md border border-emerald-200 bg-emerald-50 p-4">
                        <dt class="text-emerald-700">Cierres con sobrante</dt>
                        <dd class="mt-1 text-lg font-semibold text-emerald-700">{{ $cashSummary['surplus_sessions_count'] }}</dd>
                    </div>
                    <div class="rounded-md border border-red-200 bg-red-50 p-4">
                        <dt class="text-red-700">Faltante acumulado</dt>
                        <dd class="mt-1 text-lg font-semibold text-red-700">{{ Money::format($cashSummary['shortage_total_amount']) }}</dd>
                    </div>
                    <div class="rounded-md border border-emerald-200 bg-emerald-50 p-4">
                        <dt class="text-emerald-700">Sobrante acumulado</dt>
                        <dd class="mt-1 text-lg font-semibold text-emerald-700">{{ Money::format($cashSummary['surplus_total_amount']) }}</dd>
                    </div>
                    <div class="rounded-md border border-gray-200 p-4">
                        <dt class="text-gray-500">Peor desvío del período</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900">
                            {{ Money::format(max($cashSummary['largest_shortage_amount'], $cashSummary['largest_surplus_amount'])) }}
                        </dd>
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Caja</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Apertura</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Cierre</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Efectivo esperado</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Efectivo contado</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Transferencia esperada</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($closedSessions as $session)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">Caja #{{ $session->id }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ optional($session->opened_at)->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ optional($session->closed_at)->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ Money::format($session->expected_cash_amount ?? 0) }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ Money::format($session->counted_cash_amount ?? 0) }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ Money::format($session->expected_transfer_amount ?? 0) }}</td>
                                    <td @class([
                                        'px-4 py-3 font-medium',
                                        'text-emerald-700' => ($session->difference_amount ?? 0) > 0,
                                        'text-red-700' => ($session->difference_amount ?? 0) < 0,
                                        'text-gray-700' => ($session->difference_amount ?? 0) === 0,
                                    ])>{{ Money::format($session->difference_amount ?? 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">No hay cierres de caja en el rango seleccionado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 rounded-lg border border-gray-200">
                    <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                        <h4 class="font-medium text-gray-900">Composición del movimiento de caja</h4>
                        <p class="mt-1 text-sm text-gray-600">Desglose neto por tipo de movimiento y método dentro de las cajas cerradas del período.</p>
                    </div>

                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-white">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Tipo</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Efectivo</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Transferencia</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Otros</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Total neto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($cashMovementAnalysis['rows'] as $row)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $row['label'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ Money::format($row['cash_amount']) }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ Money::format($row['transfer_amount']) }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ Money::format($row['other_amount']) }}</td>
                                        <td @class([
                                            'px-4 py-3 font-medium',
                                            'text-emerald-700' => $row['total_amount'] > 0,
                                            'text-red-700' => $row['total_amount'] < 0,
                                            'text-gray-700' => $row['total_amount'] === 0,
                                        ])>{{ Money::format($row['total_amount']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay movimientos de caja cerrada en el rango seleccionado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($cashMovementAnalysis['rows'] !== [])
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-900">Total neto</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-900">{{ Money::format($cashMovementAnalysis['totals']['cash_amount']) }}</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-900">{{ Money::format($cashMovementAnalysis['totals']['transfer_amount']) }}</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-900">{{ Money::format($cashMovementAnalysis['totals']['other_amount']) }}</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-900">{{ Money::format($cashMovementAnalysis['totals']['total_amount']) }}</th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="font-semibold text-gray-900">Estado actual</h3>
                    @if ($currentSession)
                        <dl class="mt-4 grid gap-4 md:grid-cols-2 text-sm">
                            <div><dt class="text-gray-500">Abierta por</dt><dd class="font-medium text-gray-900">{{ $currentSession->opener?->name }}</dd></div>
                            <div><dt class="text-gray-500">Fecha</dt><dd class="font-medium text-gray-900">{{ optional($currentSession->opened_at)->format('Y-m-d H:i') }}</dd></div>
                            <div><dt class="text-gray-500">Monto inicial</dt><dd class="font-medium text-gray-900">{{ Money::format($currentSession->opening_amount) }}</dd></div>
                            <div><dt class="text-gray-500">Estado</dt><dd class="font-medium text-gray-900">Abierta</dd></div>
                        </dl>

                        <form method="POST" action="{{ route('cash.movements.store', $currentSession) }}" class="mt-6 space-y-4 border-t border-gray-100 pt-6">
                            @csrf
                            <h4 class="font-medium text-gray-900">Registrar movimiento manual</h4>
                            <div class="grid gap-4 md:grid-cols-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tipo</label>
                                    <select name="movement_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="expense">Gasto</option>
                                        <option value="withdrawal">Retiro</option>
                                        <option value="manual_income">Ingreso extraordinario</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Monto</label>
                                    <input name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Método</label>
                                    <select name="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="cash">Efectivo</option>
                                        <option value="transfer">Transferencia</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Notas</label>
                                    <input name="notes" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                            </div>
                            <button class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Guardar movimiento</button>
                        </form>
                    @else
                        <p class="mt-4 text-sm text-gray-600">No hay una caja abierta en este momento.</p>
                    @endif
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="font-semibold text-gray-900">Últimas cajas</h3>
                    <div class="mt-4 space-y-4">
                        @forelse ($sessions as $session)
                            <div class="rounded-md border border-gray-200 p-4 text-sm">
                                <p class="font-medium text-gray-900">Caja #{{ $session->id }}</p>
                                <p class="text-gray-600">{{ optional($session->opened_at)->format('Y-m-d H:i') }}</p>
                                <p class="mt-2 text-gray-700">Estado: {{ $session->status }}</p>
                                <p class="text-gray-700">Apertura: {{ Money::format($session->opening_amount) }}</p>
                                @if ($session->status === 'closed')
                                    <p class="text-gray-700">Diferencia: {{ Money::format($session->difference_amount ?? 0) }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-600">Aún no hay sesiones de caja.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
