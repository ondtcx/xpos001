<x-app-layout>
    <x-slot name="header">
        <x-page-header title="XPOS - Núcleo operativo"
                       description="Catálogo, compras, ventas, fiado, caja y reportes ya viven en el mismo circuito operativo.">
            <x-slot name="action">
                <x-status-badge tone="success">Iteración 1 avanzada</x-status-badge>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Status card + Operations grid --}}
            <div class="grid gap-6 xl:grid-cols-3">
                <x-partials.catalog-card class="xl:col-span-2">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Estado del producto</p>
                            <p class="mt-2 text-lg font-semibold text-gray-900">El sistema ya sostiene operación local con trazabilidad transaccional y lectura auditiva.</p>
                        </div>
                        <x-status-badge tone="neutral">Local-first POS</x-status-badge>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <x-stat-card label="Ventas" href="{{ route('sales.index') }}" class="hover:ring-catalog-primary hover:bg-catalog-accent">
                            <p class="font-semibold text-gray-900">Registrar, anular y auditar ventas</p>
                        </x-stat-card>
                        <x-stat-card label="Compras" href="{{ route('purchases.index') }}" class="hover:ring-catalog-primary hover:bg-catalog-accent">
                            <p class="font-semibold text-gray-900">Controlar entradas, lotes y bloqueos</p>
                        </x-stat-card>
                        <x-stat-card label="Caja" href="{{ route('cash.index') }}" class="hover:ring-catalog-primary hover:bg-catalog-accent">
                            <p class="font-semibold text-gray-900">Abrir, mover y cerrar caja</p>
                        </x-stat-card>
                        <x-stat-card label="Reportes" href="{{ route('reports.index') }}" class="hover:ring-catalog-primary hover:bg-catalog-accent">
                            <p class="font-semibold text-gray-900">Leer bruto/neto y saltar a detalle</p>
                        </x-stat-card>
                    </x-partials.catalog-card>

                {{-- Criteria card --}}
                <x-partials.catalog-card>
                    <p class="text-sm font-medium text-gray-500">Criterio de operación</p>
                    <div class="mt-4 space-y-3 text-sm text-gray-700">
                        <p><span class="font-semibold text-gray-900">Dinero:</span> todo en centavos.</p>
                        <p><span class="font-semibold text-gray-900">Costo:</span> por lote, no promedio.</p>
                        <p><span class="font-semibold text-gray-900">Ventas:</span> no se editan; se anulan con trazabilidad.</p>
                        <p><span class="font-semibold text-gray-900">Compras:</span> se corrigen solo si sus lotes no fueron consumidos.</p>
                        <p><span class="font-semibold text-gray-900">Reportes:</span> ya distinguen bruto, anulado/revertido y neto.</p>
                    </div>
                </x-partials.catalog-card>
            </div>

            {{-- Catalog grid --}}
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Categorías" href="{{ route('categories.index') }}" class="hover:ring-catalog-primary">
                    <p class="text-lg font-semibold text-gray-900">Administrar catálogo base</p>
                </x-stat-card>
                <x-stat-card label="Marcas" href="{{ route('brands.index') }}" class="hover:ring-catalog-primary">
                    <p class="text-lg font-semibold text-gray-900">Registrar marcas activas</p>
                </x-stat-card>
                <x-stat-card label="Unidades base" href="{{ route('base-units.index') }}" class="hover:ring-catalog-primary">
                    <p class="text-lg font-semibold text-gray-900">Definir unidad y símbolo</p>
                </x-stat-card>
                <x-stat-card label="Productos" href="{{ route('products.index') }}" class="hover:ring-catalog-primary">
                    <p class="text-lg font-semibold text-gray-900">Crear productos, variantes y precios</p>
                </x-stat-card>
            </div>

            {{-- Suppliers / Purchases / Sales / Reports grid --}}
            <div class="mt-6 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Proveedores" href="{{ route('suppliers.index') }}" class="hover:ring-catalog-primary">
                    <p class="text-lg font-semibold text-gray-900">Gestionar proveedores y relaciones de compra</p>
                </x-stat-card>
                <x-stat-card label="Compra rápida" href="{{ route('purchases.create') }}" class="hover:ring-catalog-primary">
                    <p class="text-lg font-semibold text-gray-900">Registrar entradas de mercadería</p>
                </x-stat-card>
                <x-stat-card label="Venta rápida" href="{{ route('sales.create') }}" class="hover:ring-catalog-primary">
                    <p class="text-lg font-semibold text-gray-900">Registrar ventas y pagos</p>
                </x-stat-card>
                <x-stat-card label="Reportes" href="{{ route('reports.index') }}" class="hover:ring-catalog-primary">
                    <p class="text-lg font-semibold text-gray-900">Consultar resultados operativos</p>
                </x-stat-card>
            </div>

            {{-- Next focus + Quick links --}}
            <x-partials.catalog-card class="mt-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <p class="text-lg font-medium text-gray-900">Siguiente foco recomendado</p>
                        <p class="mt-2 text-sm text-gray-600">El núcleo ya registra y explica la operación. Lo siguiente con mejor retorno es seguir puliendo consistencia visual y luego enriquecer exportaciones.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <x-signal-badge tone="success">Captura estable</x-signal-badge>
                            <x-signal-badge tone="neutral">Detalle operativo</x-signal-badge>
                            <x-signal-badge tone="warning">UX fina pendiente</x-signal-badge>
                        </div>
                    </div>
                    <div class="text-sm text-gray-700">
                        <p class="font-semibold text-gray-900">Accesos rápidos</p>
                        <div class="mt-3 space-y-2">
                            <p><a href="{{ route('pos.index') }}" class="text-catalog-primary hover:text-catalog-accent">POS de mostrador</a></p>
                            <p><a href="{{ route('customers.index') }}" class="text-catalog-primary hover:text-catalog-accent">Clientes y fiados</a></p>
                            <p><a href="{{ route('inventory-lots.index') }}" class="text-catalog-primary hover:text-catalog-accent">Lotes de inventario</a></p>
                            <p><a href="{{ route('suppliers.index') }}" class="text-catalog-primary hover:text-catalog-accent">Proveedores</a></p>
                        </div>
                    </div>
                </div>
            </x-partials.catalog-card>
        </div>
    </div>
</x-app-layout>
