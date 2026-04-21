@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Caja</h2>
            @if ($currentSession)
                <a href="{{ route('cash.close-form', $currentSession) }}" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white">Cerrar caja actual</a>
            @else
                <a href="{{ route('cash.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Abrir caja</a>
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
                            <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Guardar movimiento</button>
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
