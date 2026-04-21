<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('XPOS - Panel inicial') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('categories.index') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300">
                    <p class="text-sm font-medium text-gray-500">Categorías</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Administrar catálogo base</p>
                </a>
                <a href="{{ route('brands.index') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300">
                    <p class="text-sm font-medium text-gray-500">Marcas</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Registrar marcas activas</p>
                </a>
                <a href="{{ route('base-units.index') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300">
                    <p class="text-sm font-medium text-gray-500">Unidades base</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Definir unidad y símbolo</p>
                </a>
                <a href="{{ route('products.index') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300">
                    <p class="text-sm font-medium text-gray-500">Productos</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Crear productos, variantes y precios</p>
                </a>
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('suppliers.index') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300">
                    <p class="text-sm font-medium text-gray-500">Proveedores</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Gestionar proveedores y relaciones de compra</p>
                </a>
                <a href="{{ route('purchases.create') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300">
                    <p class="text-sm font-medium text-gray-500">Compra rápida</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Registrar entradas de mercadería</p>
                </a>
                <a href="{{ route('sales.create') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300">
                    <p class="text-sm font-medium text-gray-500">Venta rápida</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Registrar ventas y pagos</p>
                </a>
                <a href="{{ route('reports.index') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300">
                    <p class="text-sm font-medium text-gray-500">Reportes</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Consultar resultados operativos</p>
                </a>
            </div>

            <div class="mt-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p class="text-lg font-medium text-gray-900">{{ __('Base técnica inicial lista') }}</p>
                <p class="mt-2 text-sm text-gray-600">
                    {{ __('Ya puedes cargar el catálogo mínimo antes de continuar con proveedores, compras e inventario.') }}
                </p>
                <div class="mt-4 text-sm text-gray-700">
                    <p><span class="font-semibold">Usuario:</span> admin</p>
                    <p><span class="font-semibold">Email:</span> admin@xpos.local</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
