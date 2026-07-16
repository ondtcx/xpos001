<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Productos" description="Administra los productos del catálogo.">
            <x-slot name="action">
                <a href="{{ route('products.create') }}"
                   class="rounded-md bg-catalog-primary px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
                    Nuevo producto
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-catalog-border">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Producto</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Categoría</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Marca</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Variantes</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($products as $product)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                    @if ($product->internal_code)
                                        <div class="text-xs text-gray-500">Código: {{ $product->internal_code }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $product->category?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $product->brand?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ ucfirst($product->status) }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $product->variants->count() }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-4">
                                        <a href="{{ route('products.variants.index', $product) }}" class="text-catalog-primary hover:text-catalog-accent">Variantes</a>
                                        <a href="{{ route('products.edit', $product) }}" class="text-catalog-primary hover:text-catalog-accent">Editar</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-500">Aún no hay productos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
