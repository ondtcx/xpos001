<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Variantes de {{ $product->name }}" description="Define versiones específicas del producto.">
            <x-slot name="action">
                <a href="{{ route('products.variants.create', $product) }}"
                   class="rounded-md bg-catalog-primary px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
                    Nueva variante
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="mb-4 text-sm"><a href="{{ route('products.index') }}" class="text-catalog-primary hover:text-catalog-accent">← Volver a productos</a></div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-catalog-border">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Variante</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Unidad base</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($variants as $variant)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">
                                    <div class="font-medium">{{ $variant->name }}</div>
                                    <div class="text-xs text-gray-500">SKU: {{ $variant->sku ?: '—' }} | Código de barras: {{ $variant->barcode ?: '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $variant->baseUnit?->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $variant->is_active ? 'Activa' : 'Inactiva' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-4">
                                        <a href="{{ route('products.variants.presentations.index', [$product, $variant]) }}" class="text-catalog-primary hover:text-catalog-accent">Presentaciones</a>
                                        <a href="{{ route('products.variants.edit', [$product, $variant]) }}" class="text-catalog-primary hover:text-catalog-accent">Editar</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">Aún no hay variantes registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
