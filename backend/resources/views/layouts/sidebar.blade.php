<aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-gray-200 bg-white md:flex">
    <!-- Brand -->
    <div class="flex h-16 items-center border-b border-gray-200 px-6">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
            <span class="text-lg font-semibold text-gray-900">{{ config('app.name', 'Laravel') }}</span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 space-y-1 px-3 py-4">
        @php
            $navItems = [
                ['route' => 'dashboard',         'label' => 'Inicio',       'patterns' => ['dashboard']],
                ['route' => 'categories.index',  'label' => 'Categorías',   'patterns' => ['categories.*']],
                ['route' => 'brands.index',      'label' => 'Marcas',       'patterns' => ['brands.*']],
                ['route' => 'base-units.index',  'label' => 'Unidades',     'patterns' => ['base-units.*']],
                ['route' => 'products.index',    'label' => 'Productos',    'patterns' => ['products.*']],
                ['route' => 'suppliers.index',   'label' => 'Proveedores',  'patterns' => ['suppliers.*']],
                ['route' => 'purchases.index',   'label' => 'Compras',      'patterns' => ['purchases.*']],
                ['route' => 'opening-inventory.index', 'label' => 'Inventario', 'patterns' => ['opening-inventory.*', 'inventory-lots.*']],
                ['route' => 'customers.index',   'label' => 'Clientes',     'patterns' => ['customers.*', 'receivables.*']],
                ['route' => 'sales.index',       'label' => 'Ventas',       'patterns' => ['sales.*']],
                ['route' => 'cash.index',        'label' => 'Caja',         'patterns' => ['cash.*']],
                ['route' => 'reports.index',     'label' => 'Reportes',     'patterns' => ['reports.*']],
            ];
        @endphp

        @foreach($navItems as $item)
            @php
                $isActive = false;
                foreach ($item['patterns'] as $pattern) {
                    if (request()->routeIs($pattern)) {
                        $isActive = true;
                        break;
                    }
                }
            @endphp
            <a href="{{ route($item['route']) }}"
               class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150
                      {{ $isActive ? 'bg-emerald-50 text-emerald-700' : 'text-gray-700 hover:bg-gray-50' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <!-- Placeholder for user block (PR #4) -->
    <div class="mt-auto"></div>
</aside>
