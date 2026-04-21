@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Fiados y cuentas por cobrar</h2>
            <a href="{{ route('customers.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Ver clientes</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif
            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Fecha</th>
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
                                <td class="px-4 py-3 text-gray-900">{{ $receivable->customer->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ Money::format($receivable->original_amount) }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ Money::format($receivable->pending_amount) }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $receivable->status }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('receivables.show', $receivable) }}" class="text-indigo-600">Ver detalle</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Aún no hay cuentas por cobrar registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
