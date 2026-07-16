@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Historial de precios — {{ $presentation->name }}" description="{{ $product->name }} / {{ $variant->name }}">
            <x-slot name="action">
                <a href="{{ route('products.variants.presentations.prices.create', [$product, $variant, $presentation]) }}"
                   class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
                    Nuevo precio
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="mb-4 text-sm"><a href="{{ route('products.variants.presentations.index', [$product, $variant]) }}" class="text-emerald-700 hover:text-emerald-900">← Volver a presentaciones</a></div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Precio</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Precio mínimo</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Margen sugerido</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Inicio</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Fin</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Usuario</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Motivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($prices as $price)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ Money::format($price->price_amount) }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $price->min_price_amount !== null ? Money::format($price->min_price_amount) : '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $price->suggested_margin_percent !== null ? number_format((float) $price->suggested_margin_percent, 2) . '%' : '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ optional($price->starts_at)->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ optional($price->ends_at)->format('Y-m-d H:i') ?? 'Vigente' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $price->creator?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $price->reason ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-500">Aún no hay precios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
