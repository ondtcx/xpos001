@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Clientes</h2>
            <a href="{{ route('customers.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Nuevo cliente</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif
            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Teléfono</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Saldo pendiente</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($customers as $customer)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $customer->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $customer->phone ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ Money::format((int) ($customer->pending_receivable_amount ?? 0)) }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $customer->is_active ? 'Activo' : 'Inactivo' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-4">
                                        <a href="{{ route('receivables.index') }}" class="text-indigo-600">Fiados</a>
                                        <a href="{{ route('customers.edit', $customer) }}" class="text-indigo-600">Editar</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Aún no hay clientes registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
