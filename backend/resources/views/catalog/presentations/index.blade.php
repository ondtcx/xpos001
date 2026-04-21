@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Presentaciones de {{ $variant->name }}</h2>
                <p class="text-sm text-gray-500">Producto: {{ $product->name }}</p>
            </div>
            <a href="{{ route('products.variants.presentations.create', [$product, $variant]) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Nueva presentación</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="mb-4 text-sm"><a href="{{ route('products.variants.index', $product) }}" class="text-indigo-600">← Volver a variantes</a></div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Nombre</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Factor</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Precio vigente</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($presentations as $presentation)
                            @php($currentPrice = $presentation->prices->firstWhere('ends_at', null) ?? $presentation->prices->first())
                            <tr>
                                <td class="px-4 py-3 text-gray-900">
                                    <div class="font-medium">{{ $presentation->name }}</div>
                                    @if ($presentation->is_default)
                                        <span class="text-xs text-indigo-600">Predeterminada</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ number_format((float) $presentation->conversion_factor, 3, '.', '') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $currentPrice ? Money::format($currentPrice->price_amount) : 'Sin precio' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $presentation->is_active ? 'Activa' : 'Inactiva' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-4">
                                        <a href="{{ route('products.variants.presentations.prices.index', [$product, $variant, $presentation]) }}" class="text-indigo-600">Precios</a>
                                        <a href="{{ route('products.variants.presentations.edit', [$product, $variant, $presentation]) }}" class="text-indigo-600">Editar</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Aún no hay presentaciones registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
