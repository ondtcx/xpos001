@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Cuenta por cobrar #{{ $receivable->id }}</h2>
                <p class="text-sm text-gray-500">Cliente: {{ $receivable->customer->name }}</p>
            </div>
            <a href="{{ route('receivables.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Volver</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-3 sm:px-6 lg:px-8">
            <div class="lg:col-span-2 space-y-6">
                @if (session('status'))
                    <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
                @endif

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="font-semibold text-gray-900">Resumen</h3>
                    <dl class="mt-4 grid gap-4 md:grid-cols-2 text-sm">
                        <div><dt class="text-gray-500">Original</dt><dd class="font-medium text-gray-900">{{ Money::format($receivable->original_amount) }}</dd></div>
                        <div><dt class="text-gray-500">Pendiente</dt><dd class="font-medium text-gray-900">{{ Money::format($receivable->pending_amount) }}</dd></div>
                        <div><dt class="text-gray-500">Fecha</dt><dd class="font-medium text-gray-900">{{ optional($receivable->opened_at)->format('Y-m-d H:i') }}</dd></div>
                        <div><dt class="text-gray-500">Estado</dt><dd class="font-medium text-gray-900">{{ $receivable->status }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="font-semibold text-gray-900">Seguimiento de cobranza</h3>
                    <dl class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-5 text-sm">
                        <div>
                            <dt class="text-gray-500">Antigüedad</dt>
                            <dd class="font-medium text-gray-900">{{ $receivableTracking['days_open'] }} días</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Cobrado</dt>
                            <dd class="font-medium text-gray-900">{{ Money::format($receivableTracking['paid_amount']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Avance</dt>
                            <dd class="font-medium text-gray-900">{{ $receivableTracking['collection_progress'] }}%</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Último abono</dt>
                            <dd class="font-medium text-gray-900">{{ optional($receivableTracking['last_payment_at'])->format('Y-m-d H:i') ?? 'Sin abonos' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Prioridad</dt>
                            <dd @class([
                                'font-medium',
                                'text-emerald-700' => $receivableTracking['aging_label'] === 'Deuda reciente',
                                'text-amber-700' => $receivableTracking['aging_label'] === 'Seguimiento activo',
                                'text-red-700' => $receivableTracking['aging_label'] === 'Deuda antigua',
                                'text-gray-900' => $receivableTracking['aging_label'] === 'Cuenta cerrada',
                            ])>{{ $receivableTracking['aging_label'] }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="font-semibold text-gray-900">Historial de abonos</h3>
                    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Fecha</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Método</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Monto</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Usuario</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($receivable->payments as $payment)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-700">{{ optional($payment->paid_at)->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $payment->payment_method }}</td>
                                        <td class="px-4 py-3 text-gray-900">{{ Money::format($payment->amount) }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $payment->creator?->name ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Aún no hay abonos.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div>
                @if (! $currentCashSession)
                    <div class="mb-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        No hay una caja abierta. No podrás registrar abonos hasta abrir caja.
                    </div>
                @endif

                <form method="POST" action="{{ route('receivables.payments.store', $receivable) }}" class="space-y-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    @csrf
                    <h3 class="font-semibold text-gray-900">Registrar abono</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Monto</label>
                        <input name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Método de pago</label>
                        <select name="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="cash">Efectivo</option>
                            <option value="transfer">Transferencia</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha</label>
                        <input name="paid_at" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notas</label>
                        <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <button class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Guardar abono</button>
                    @if ($errors->has('amount'))
                        <p class="text-sm text-red-600">{{ $errors->first('amount') }}</p>
                    @endif
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
