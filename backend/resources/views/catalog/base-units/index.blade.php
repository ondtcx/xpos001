<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Unidades base</h2>
            <a href="{{ route('base-units.create') }}" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Nueva unidad</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Símbolo</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($baseUnits as $baseUnit)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $baseUnit->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $baseUnit->symbol }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('base-units.edit', $baseUnit) }}" class="text-emerald-600 hover:text-emerald-800">Editar</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">Aún no hay unidades base registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
