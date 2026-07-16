<div x-data="{ open: false }" @keydown.escape.window="open = false">
    <!-- Mobile backdrop overlay (visible only when drawer is open) -->
    <div x-show="open" @click="open = false" class="fixed inset-0 z-20 bg-black/40 md:hidden"></div>

    <!-- Hamburger toggle (mobile only) -->
    <button @click="open = true" class="fixed top-4 left-4 z-40 md:hidden" aria-label="Open sidebar">
        <svg class="h-6 w-6 text-gray-700" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
    </button>

    <aside
        class="fixed inset-y-0 left-0 z-30 w-64 flex-col border-r border-gray-200 bg-white hidden md:flex"
        :class="{ '!flex': open }"
    >
        <!-- Brand -->
        <div class="flex h-16 items-center border-b border-gray-200 px-6">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                <span class="text-lg font-semibold text-gray-900">{{ config('app.name', 'Laravel') }}</span>
            </a>
            <!-- Close button (mobile only) -->
            <button @click="open = false" class="ml-auto md:hidden" aria-label="Close sidebar">
                <svg class="h-5 w-5 text-gray-500" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 space-y-1 px-3 py-4">
            @php
                // Heroicon name → SVG path mapping (outline style, Heroicons v2)
                $heroicons = [
                    'home' => '<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />',
                    'tag' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />',
                    'bookmark' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />',
                    'cube' => '<path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />',
                    'archive-box' => '<path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />',
                    'truck' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.25 2.25 0 0 0-1.817-.933H11.25m0 0-.256-1.253a2.25 2.25 0 0 0-2.213-1.747H3.75a1.125 1.125 0 0 0 0 2.25h4.28l1.22 6.003m0 0h6m-6 0 1.22 6.003" />',
                    'arrow-down-tray' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />',
                    'inbox-arrow-down' => '<path stroke-linecap="round" stroke-linejoin="round" d="m9 8.25 3 3m0 0 3-3m-3 3V3M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5" />',
                    'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />',
                    'currency-dollar' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
                    'banknotes' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v9.575c0 .621.504 1.125 1.125 1.125h.75m-1.5-9.75h.375c.621 0 1.125.504 1.125 1.125V10.5M8.25 12a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0Z" />',
                    'chart-bar' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />',
                ];

                $navItems = [
                    ['route' => 'dashboard',         'label' => 'Inicio',       'patterns' => ['dashboard'],                         'icon' => 'home'],
                    ['route' => 'categories.index',  'label' => 'Categorías',   'patterns' => ['categories.*'],                       'icon' => 'tag'],
                    ['route' => 'brands.index',      'label' => 'Marcas',       'patterns' => ['brands.*'],                           'icon' => 'bookmark'],
                    ['route' => 'base-units.index',  'label' => 'Unidades',     'patterns' => ['base-units.*'],                       'icon' => 'cube'],
                    ['route' => 'products.index',    'label' => 'Productos',    'patterns' => ['products.*'],                         'icon' => 'archive-box'],
                    ['route' => 'suppliers.index',   'label' => 'Proveedores',  'patterns' => ['suppliers.*'],                        'icon' => 'truck'],
                    ['route' => 'purchases.index',   'label' => 'Compras',      'patterns' => ['purchases.*'],                        'icon' => 'arrow-down-tray'],
                    ['route' => 'inventory-stock.index', 'label' => 'Inventario', 'patterns' => ['inventory-stock.*', 'opening-inventory.*', 'inventory-lots.*'], 'icon' => 'inbox-arrow-down'],
                    ['route' => 'customers.index',   'label' => 'Clientes',     'patterns' => ['customers.*', 'receivables.*'],       'icon' => 'users'],
                    ['route' => 'sales.index',       'label' => 'Ventas',       'patterns' => ['sales.*'],                            'icon' => 'currency-dollar'],
                    ['route' => 'cash.index',        'label' => 'Caja',         'patterns' => ['cash.*'],                             'icon' => 'banknotes'],
                    ['route' => 'reports.index',     'label' => 'Reportes',     'patterns' => ['reports.*'],                          'icon' => 'chart-bar'],
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
                <x-nav-link :active="$isActive" href="{{ route($item['route']) }}">
                    <svg class="h-5 w-5 shrink-0 {{ $isActive ? 'text-current' : 'text-gray-400 group-hover:text-gray-500' }}" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        {!! $heroicons[$item['icon']] !!}
                    </svg>
                    <span class="ml-3">{{ $item['label'] }}</span>
                </x-nav-link>
            @endforeach
        </nav>

        <!-- User block -->
        <div class="mt-auto border-t border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-semibold">
                    @php
                        $nameParts = explode(' ', trim(Auth::user()->name));
                        $initials = '';
                        if (count($nameParts) >= 2) {
                            $firstInitial = strtoupper(substr($nameParts[0], 0, 1));
                            $lastInitial = strtoupper(substr(end($nameParts), 0, 1));
                            $initials = $firstInitial . $lastInitial;
                        } else {
                            $initials = strtoupper(substr($nameParts[0], 0, 2));
                        }
                    @endphp
                    <span class="text-sm">{{ $initials }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                    <p class="truncate text-xs text-gray-500">{{ Auth::user()->email }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="text-sm text-gray-700 hover:text-gray-900">Log out</button>
            </form>
        </div>
    </aside>
</div>
