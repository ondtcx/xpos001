<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">XPOS - Núcleo operativo</h2>
                <p class="mt-1 text-sm text-gray-500">Catálogo, compras, ventas, fiado, caja y reportes ya viven en el mismo circuito operativo.</p>
            </div>
            <x-status-badge tone="success">Iteración 1 avanzada</x-status-badge>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 xl:grid-cols-3">
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 xl:col-span-2">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Estado del producto</p>
                            <p class="mt-2 text-lg font-semibold text-gray-900">El sistema ya sostiene operación local con trazabilidad transaccional y lectura auditiva.</p>
                        </div>
                        <x-status-badge tone="info">Local-first POS</x-status-badge>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <a href="{{ route('sales.index') }}" class="rounded-lg border border-gray-200 p-4 hover:border-emerald-300 hover:bg-emerald-50/30">
                            <p class="text-sm text-gray-500">Ventas</p>
                            <p class="mt-1 font-semibold text-gray-900">Registrar, anular y auditar ventas</p>
                        </a>
                        <a href="{{ route('purchases.index') }}" class="rounded-lg border border-gray-200 p-4 hover:border-emerald-300 hover:bg-emerald-50/30">
                            <p class="text-sm text-gray-500">Compras</p>
                            <p class="mt-1 font-semibold text-gray-900">Controlar entradas, lotes y bloqueos</p>
                        </a>
                        <a href="{{ route('cash.index') }}" class="rounded-lg border border-gray-200 p-4 hover:border-emerald-300 hover:bg-emerald-50/30">
                            <p class="text-sm text-gray-500">Caja</p>
                            <p class="mt-1 font-semibold text-gray-900">Abrir, mover y cerrar caja</p>
                        </a>
                        <a href="{{ route('reports.index') }}" class="rounded-lg border border-gray-200 p-4 hover:border-emerald-300 hover:bg-emerald-50/30">
                            <p class="text-sm text-gray-500">Reportes</p>
                            <p class="mt-1 font-semibold text-gray-900">Leer bruto/neto y saltar a detalle</p>
                        </a>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-500">Criterio de operación</p>
                    <div class="mt-4 space-y-3 text-sm text-gray-700">
                        <p><span class="font-semibold text-gray-900">Dinero:</span> todo en centavos.</p>
                        <p><span class="font-semibold text-gray-900">Costo:</span> por lote, no promedio.</p>
                        <p><span class="font-semibold text-gray-900">Ventas:</span> no se editan; se anulan con trazabilidad.</p>
                        <p><span class="font-semibold text-gray-900">Compras:</span> se corrigen solo si sus lotes no fueron consumidos.</p>
                        <p><span class="font-semibold text-gray-900">Reportes:</span> ya distinguen bruto, anulado/revertido y neto.</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('categories.index') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-emerald-300">
                    <p class="text-sm font-medium text-gray-500">Categorías</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Administrar catálogo base</p>
                </a>
                <a href="{{ route('brands.index') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-emerald-300">
                    <p class="text-sm font-medium text-gray-500">Marcas</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Registrar marcas activas</p>
                </a>
                <a href="{{ route('base-units.index') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-emerald-300">
                    <p class="text-sm font-medium text-gray-500">Unidades base</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Definir unidad y símbolo</p>
                </a>
                <a href="{{ route('products.index') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-emerald-300">
                    <p class="text-sm font-medium text-gray-500">Productos</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Crear productos, variantes y precios</p>
                </a>
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('suppliers.index') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-emerald-300">
                    <p class="text-sm font-medium text-gray-500">Proveedores</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Gestionar proveedores y relaciones de compra</p>
                </a>
                <a href="{{ route('purchases.create') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-emerald-300">
                    <p class="text-sm font-medium text-gray-500">Compra rápida</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Registrar entradas de mercadería</p>
                </a>
                <a href="{{ route('sales.create') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-emerald-300">
                    <p class="text-sm font-medium text-gray-500">Venta rápida</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Registrar ventas y pagos</p>
                </a>
                <a href="{{ route('reports.index') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 hover:ring-emerald-300">
                    <p class="text-sm font-medium text-gray-500">Reportes</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900">Consultar resultados operativos</p>
                </a>
            </div>

            <div class="mt-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <p class="text-lg font-medium text-gray-900">Siguiente foco recomendado</p>
                        <p class="mt-2 text-sm text-gray-600">El núcleo ya registra y explica la operación. Lo siguiente con mejor retorno es seguir puliendo consistencia visual y luego enriquecer exportaciones.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <x-signal-badge tone="success">Captura estable</x-signal-badge>
                            <x-signal-badge tone="info">Detalle operativo</x-signal-badge>
                            <x-signal-badge tone="warning">UX fina pendiente</x-signal-badge>
                        </div>
                    </div>
                    <div class="text-sm text-gray-700">
                        <p class="font-semibold text-gray-900">Accesos rápidos</p>
                        <div class="mt-3 space-y-2">
                            <p><a href="{{ route('pos.index') }}" class="text-emerald-700 hover:text-emerald-900">POS de mostrador</a></p>
                            <p><a href="{{ route('customers.index') }}" class="text-emerald-700 hover:text-emerald-900">Clientes y fiados</a></p>
                            <p><a href="{{ route('inventory-lots.index') }}" class="text-emerald-700 hover:text-emerald-900">Lotes de inventario</a></p>
                            <p><a href="{{ route('suppliers.index') }}" class="text-emerald-700 hover:text-emerald-900">Proveedores</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
