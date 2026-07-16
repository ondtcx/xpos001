@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Cerrar caja #{{ $cashSession->id }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <dl class="grid gap-4 md:grid-cols-2 text-sm">
                    <div><dt class="text-gray-500">Efectivo esperado</dt><dd class="font-medium text-gray-900">{{ Money::format($expectedCash) }}</dd></div>
                    <div><dt class="text-gray-500">Transferencia esperada</dt><dd class="font-medium text-gray-900">{{ Money::format($expectedTransfer) }}</dd></div>
                </dl>
            </div>

            <form method="POST" action="{{ route('cash.close', $cashSession) }}" class="space-y-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Fecha de cierre</label>
                    <input name="closed_at" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Efectivo contado</label>
                    <input name="counted_cash_amount" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Observaciones</label>
                    <textarea name="closing_notes" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('cash.index') }}" class="text-sm text-gray-600">Cancelar</a>
                    <button class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Cerrar caja</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
