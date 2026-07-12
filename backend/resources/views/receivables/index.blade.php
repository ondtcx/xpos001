@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Fiados y cuentas por cobrar</h2>
            <a href="{{ route('customers.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Ver clientes</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">Antigüedad de deuda</h3>
                        <p class="mt-1 text-sm text-gray-600">Resumen de cuentas abiertas para priorizar cobranza.</p>
                    </div>

                    <div class="text-sm text-gray-700">
                        <p>Cuentas abiertas: <span class="font-semibold text-gray-900">{{ $receivableAging['open_count'] }}</span></p>
                        <p>Saldo abierto: <span class="font-semibold text-gray-900">{{ Money::format($receivableAging['open_pending_amount']) }}</span></p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    @foreach ($receivableAging['buckets'] as $bucket)
                        <div class="rounded-md border border-gray-200 p-4">
                            <p class="text-sm font-medium text-gray-500">{{ $bucket['label'] }}</p>
                            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $bucket['count'] }}</p>
                            <p class="mt-1 text-sm text-gray-600">{{ Money::format($bucket['pending_amount']) }} pendientes</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Fecha</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Edad</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Cliente</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Original</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Pendiente</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($receivables as $receivable)
                            <tr>
                                <td class="px-4 py-3 text-gray-700">{{ optional($receivable->opened_at)->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    @if ($receivable->isOpen())
                                        {{ optional($receivable->opened_at)?->startOfDay()->diffInDays(now()->startOfDay()) }} días
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-900">{{ $receivable->customer->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ Money::format($receivable->original_amount) }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ Money::format($receivable->pending_amount) }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $receivable->status }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('receivables.show', $receivable) }}" class="text-emerald-600">Ver detalle</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">Aún no hay cuentas por cobrar registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
