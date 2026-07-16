@php
    /** @var \Illuminate\Database\Eloquent\Collection $presentations */
    /** @var array<int, array<string, mixed>> $clientes */
    /** @var int|null $defaultClienteId */

    $productos = $presentations->map(function ($presentation) use ($availableBaseUnitsByVariant) {
        $price = $presentation->prices->firstWhere('ends_at', null) ?? $presentation->prices->first();
        $availableBaseUnits = (float) ($availableBaseUnitsByVariant[$presentation->product_variant_id] ?? 0);
        $availableSaleUnits = (float) $presentation->conversion_factor > 0
            ? round($availableBaseUnits / (float) $presentation->conversion_factor, 3)
            : 0;
        $lot = $presentation->variant?->nearestLot;
        $lote = $lot ? 'L-'.$lot->id : null;
        $vence = $lot && $lot->expiration_date
            ? \Illuminate\Support\Carbon::parse($lot->expiration_date)->format('m/Y')
            : null;

        return [
            'id' => $presentation->id,
            'nombre' => $presentation->variant->product->name,
            'codigo' => $presentation->variant->product->internal_code,
            'barcode' => $presentation->variant->barcode,
            'categoria' => $presentation->variant->product->category?->name,
            'precio' => $price ? round($price->price_amount / 100, 2) : 0.0,
            'disponibles' => (int) floor($availableSaleUnits),
            'lote' => $lote,
            'vence' => $vence,
        ];
    })->values()->all();

    $initialV2 = [
        'productos' => $productos,
        'clientes' => $clientes,
    ];
    $initialV2Json = json_encode($initialV2, JSON_UNESCAPED_UNICODE);
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Caja / Punto de venta" description="{{ auth()->user()?->name ?? '' }}">
            <x-slot name="action">
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('sales.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Historial de ventas</a>
                    <a href="{{ route('sales.create') }}" class="rounded-md border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700">Venta completa</a>
                </div>
            </x-slot>
        </x-page-header>
    </x-slot>

    <script>window.__POS_INITIAL_V2__ = {!! $initialV2Json !!};</script>

    <div x-data x-ref="pos" class="mx-auto flex max-w-[1400px] flex-col gap-5 p-4 lg:p-6">
        <!-- Toast -->
        <div x-show="$store.posStore.aviso" x-transition class="fixed left-1/2 top-4 z-50 -translate-x-1/2"
             :class="$store.posStore.aviso?.tipo === 'error' ? 'bg-red-500' : 'bg-emerald-500'"
             style="display: none;">
            <div class="rounded-lg px-4 py-2 text-sm font-medium text-white shadow-lg">
                <span x-text="$store.posStore.aviso?.mensaje"></span>
            </div>
        </div>

        @if ($defaultClienteId === null)
            <div class="rounded-3xl border border-red-300 bg-red-50 p-6 text-center">
                <p class="text-base font-semibold text-red-700">Falta el cliente por defecto</p>
                <p class="mt-1 text-sm text-red-600">Ejecuta <code>php artisan db:seed</code> para crear el cliente &quot;Cliente General&quot;.</p>
            </div>
        @else
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[1fr_360px]">
            <!-- LEFT COLUMN -->
            <div class="flex flex-col gap-5">
                <!-- Catalog section -->
                <section class="rounded-3xl border border-gray-200 bg-white p-1">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <p class="text-sm font-semibold text-gray-900">Catálogo</p>
                        <p class="mt-1 text-xs text-gray-500">Busca por nombre, código o barcode. Toca la tarjeta para agregar.</p>
                    </div>
                    <div class="p-3">
                        <div class="relative">
                            <input type="text"
                                   x-model="$store.posStore.busqueda"
                                   placeholder="Buscar por nombre, codigo o categoria..."
                                   class="block w-full rounded-2xl border-gray-300 pr-10 text-sm shadow-sm">
                            <svg class="pointer-events-none absolute right-3 top-2.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M16 10a6 6 0 11-12 0 6 6 0 0112 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 p-3 md:grid-cols-3 xl:grid-cols-4">
                        <template x-for="p in $store.posStore.filteredProductos" :key="p.id">
                            <article class="flex flex-col gap-2 rounded-2xl border border-gray-200 bg-white p-3 text-sm shadow-sm"
                                     :class="p.disponibles <= 0 ? 'opacity-60' : ''">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700" x-text="p.categoria ?? '—'"></span>
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                                          :class="p.disponibles <= 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'"
                                          x-text="p.disponibles <= 0 ? 'Agotado' : (p.disponibles + ' disp.')"></span>
                                </div>
                                <p class="line-clamp-2 text-sm font-semibold text-gray-900" x-text="p.nombre"></p>
                                <p class="text-xs text-gray-500" x-text="(p.lote ? 'Lote ' + p.lote : 'Sin lote') + (p.vence ? ' · Vence ' + p.vence : '')"></p>
                                <div class="mt-auto flex items-center justify-between">
                                    <span class="text-base font-bold text-gray-900" x-text="$store.posStore.formatMoney(p.precio)"></span>
                                    <button type="button"
                                            @click="$store.posStore.agregar(p)"
                                            :disabled="p.disponibles <= 0"
                                            class="rounded-full bg-emerald-400 px-3 py-1 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:bg-gray-300">
                                        +
                                    </button>
                                </div>
                            </article>
                        </template>
                    </div>
                    <div x-show="$store.posStore.filteredProductos.length === 0" class="px-4 pb-4 text-sm text-gray-500" style="display: none;">
                        No se encontraron productos para <span x-text="$store.posStore.busqueda"></span>
                    </div>
                </section>

                <!-- Cart section -->
                <section class="rounded-3xl border border-gray-200 bg-slate-50 p-4">
                    <header class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 8h13"/>
                                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                            </svg>
                            <h2 class="text-base font-semibold text-gray-900">Venta actual <span class="ml-1 rounded-full bg-slate-200 px-2 py-0.5 text-xs" x-text="$store.posStore.itemsCount"></span></h2>
                        </div>
                        <div class="flex gap-2" x-show="$store.posStore.items.length > 0" style="display: none;">
                            <button type="button" @click="$store.posStore.limpiar()" class="rounded-md border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700">Vaciar</button>
                            <button type="button" @click="$store.posStore.anularVenta()" class="rounded-md border border-red-300 bg-red-50 px-3 py-1 text-xs font-medium text-red-700">Anular venta</button>
                        </div>
                    </header>

                    <div x-show="$store.posStore.items.length === 0" class="mt-6 flex flex-col items-center gap-2 py-8 text-center text-sm text-gray-500">
                        <svg class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 8h13"/>
                            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        </svg>
                        <p>Agrega productos para iniciar la venta.</p>
                    </div>

                    <ul class="mt-4 space-y-2" x-show="$store.posStore.items.length > 0" style="display: none;">
                        <template x-for="item in $store.posStore.items" :key="item.id">
                            <li class="flex flex-wrap items-center gap-3 rounded-2xl border border-gray-200 bg-white p-3 text-sm shadow-sm">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-900" x-text="item.nombre"></p>
                                    <p class="mt-0.5 text-xs text-gray-500">
                                        <span x-text="$store.posStore.formatMoney(item.precio)"></span> c/u
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 rounded-full border border-gray-200 px-2 py-0.5">
                                    <button type="button"
                                            @click="$store.posStore.cambiarCantidad(item.id, Math.max(1, item.cantidad - 1))"
                                            class="px-2 text-gray-600">−</button>
                                    <span class="min-w-[2rem] text-center text-sm font-semibold" x-text="item.cantidad"></span>
                                    <button type="button"
                                            @click="$store.posStore.cambiarCantidad(item.id, item.cantidad + 1)"
                                            class="px-2 text-gray-600">+</button>
                                </div>
                                <p class="w-24 text-right text-sm font-semibold text-gray-900" x-text="$store.posStore.formatMoney(item.precio * item.cantidad)"></p>
                                <button type="button" @click="$store.posStore.quitar(item.id)" class="text-red-500" aria-label="Quitar">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/>
                                    </svg>
                                </button>
                            </li>
                        </template>
                    </ul>
                </section>
            </div>

            <!-- RIGHT COLUMN: Checkout panel -->
            <aside class="flex max-h-[85vh] flex-col gap-4 overflow-y-auto rounded-3xl border border-gray-200 bg-white p-5">
                <!-- Customer dropdown with inline search -->
                <div class="relative" @click.outside="$store.posStore.closeCliente()">
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Cliente</label>
                    <button type="button"
                            @click="$store.posStore.toggleCliente()"
                            :aria-expanded="$store.posStore.clienteOpen"
                            :class="$store.posStore.clienteOpen ? 'border-emerald-400 bg-emerald-50' : 'border-gray-300 bg-white'"
                            class="mt-1 flex w-full items-center justify-between rounded-2xl border px-3 py-2 text-left text-sm shadow-sm">
                        <span>
                            <span class="font-semibold" x-text="$store.posStore.cliente?.nombre || 'Seleccionar'"></span>
                            <span class="ml-2 text-xs text-gray-500" x-text="$store.posStore.cliente?.documento || ''"></span>
                        </span>
                        <svg class="h-4 w-4 text-gray-400 transition-transform" :class="$store.posStore.clienteOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="$store.posStore.clienteOpen"
                         x-transition
                         class="absolute z-20 mt-1 w-full rounded-2xl border border-gray-200 bg-white shadow-lg"
                         style="display: none;">
                        <input type="text"
                               :value="$store.posStore.clienteQuery"
                               @input="$store.posStore.searchCliente($event.target.value)"
                               @keydown.escape.prevent="$store.posStore.closeCliente()"
                               @keydown.arrow-down.prevent="$store.posStore.moveClienteHighlight(1)"
                               @keydown.arrow-up.prevent="$store.posStore.moveClienteHighlight(-1)"
                               @keydown.enter.prevent="if ($store.posStore.clienteHighlight >= 0) { $store.posStore.setCliente($store.posStore.filteredClientes[$store.posStore.clienteHighlight]); }"
                               placeholder="Buscar por nombre o documento..."
                               class="block w-full rounded-t-2xl border-b border-gray-200 px-3 py-2 text-sm focus:outline-none">
                        <ul class="max-h-60 overflow-y-auto py-1 text-sm">
                            <template x-for="(c, index) in $store.posStore.filteredClientes" :key="c.id">
                                <li>
                                    <button type="button"
                                            @click="$store.posStore.setCliente(c)"
                                            :class="index === $store.posStore.clienteHighlight ? 'bg-emerald-50' : 'hover:bg-gray-50'"
                                            class="flex w-full flex-col gap-0.5 px-3 py-2 text-left">
                                        <span class="font-medium text-gray-900" x-text="c.name"></span>
                                        <span class="text-xs text-gray-500">
                                            <span x-text="c.document || '—'"></span>
                                            <template x-if="c.saldo_fiado > 0">
                                                <span class="ml-2 text-amber-700" x-text="'debe ' + $store.posStore.formatMoney(c.saldo_fiado)"></span>
                                            </template>
                                        </span>
                                    </button>
                                </li>
                            </template>
                            <li x-show="$store.posStore.clienteSearching" class="px-3 py-2 text-xs text-gray-500">Buscando...</li>
                            <li x-show="!$store.posStore.clienteSearching && $store.posStore.filteredClientes.length === 0" class="px-3 py-2 text-xs text-gray-500">Sin resultados.</li>
                        </ul>
                        <button type="button"
                                @click="$store.posStore.openClienteCreate()"
                                class="block w-full rounded-b-2xl border-t border-gray-200 px-3 py-2 text-left text-sm font-medium text-emerald-700 hover:bg-emerald-50">
                            + Nuevo cliente
                        </button>
                    </div>
                </div>

                <!-- Payment tabs: 3 buttons -->
                <div class="grid grid-cols-3 gap-2">
                    <button type="button"
                            @click="$store.posStore.setMetodo('efectivo')"
                            :aria-pressed="$store.posStore.metodo === 'efectivo'"
                            :class="$store.posStore.metodo === 'efectivo' ? 'border-emerald-400 bg-emerald-50 text-emerald-700' : 'border-gray-300 bg-white text-gray-700'"
                            class="rounded-2xl border px-3 py-2 text-sm font-medium">Efectivo</button>
                    <button type="button"
                            @click="$store.posStore.setMetodo('transfer')"
                            :aria-pressed="$store.posStore.metodo === 'transfer'"
                            :class="$store.posStore.metodo === 'transfer' ? 'border-emerald-400 bg-emerald-50 text-emerald-700' : 'border-gray-300 bg-white text-gray-700'"
                            class="rounded-2xl border px-3 py-2 text-sm font-medium">Transfer.</button>
                    <button type="button"
                            @click="$store.posStore.setMetodo('fiado')"
                            :disabled="$store.posStore.cliente?.id === $store.posStore.generalId"
                            :aria-pressed="$store.posStore.metodo === 'fiado'"
                            :class="[
                                $store.posStore.metodo === 'fiado' ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-gray-300 bg-white text-gray-700',
                                $store.posStore.cliente?.id === $store.posStore.generalId ? 'cursor-not-allowed opacity-50' : ''
                            ]"
                            class="rounded-2xl border px-3 py-2 text-sm font-medium">Fiado</button>
                </div>

                <!-- Conditional: Efectivo input + chips + Vuelto -->
                <div x-show="$store.posStore.metodo === 'efectivo'" class="flex flex-col gap-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Efectivo recibido</label>
                    <input type="number" step="0.01" min="0"
                           x-model="$store.posStore.recibido"
                           placeholder="0.00"
                           class="block w-full rounded-2xl border-gray-300 text-sm shadow-sm">
                    <div class="flex flex-wrap gap-2">
                        <template x-if="$store.posStore.total > 0">
                            <button type="button" @click="$store.posStore.quickAmount($store.posStore.total)" class="rounded-full border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700">Exacto</button>
                        </template>
                        <button type="button" @click="$store.posStore.quickAmount(20)" class="rounded-full border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700">USD 20</button>
                        <button type="button" @click="$store.posStore.quickAmount(50)" class="rounded-full border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700">USD 50</button>
                        <button type="button" @click="$store.posStore.quickAmount(100)" class="rounded-full border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700">USD 100</button>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                        Vuelto: <span class="font-semibold" x-text="$store.posStore.formatMoney($store.posStore.vuelto)"></span>
                    </div>
                </div>

                <!-- Conditional: Fiado banner -->
                <div x-show="$store.posStore.metodo === 'fiado'" class="rounded-2xl border border-amber-200 bg-amber-50 p-3 text-sm" style="display: none;">
                    <template x-if="$store.posStore.cliente?.id === $store.posStore.generalId">
                        <p class="text-amber-800">Selecciona un cliente para registrar el fiado.</p>
                    </template>
                    <template x-if="$store.posStore.cliente?.id !== $store.posStore.generalId">
                        <p class="text-amber-800">
                            Se sumará <span class="font-semibold" x-text="$store.posStore.formatMoney($store.posStore.total)"></span>
                            a la cuenta de <span class="font-semibold" x-text="$store.posStore.cliente?.nombre"></span>.
                        </p>
                    </template>
                </div>

                <!-- Totals -->
                <div class="flex flex-col gap-1 border-t border-gray-200 pt-3 text-sm">
                    <div class="flex items-center justify-between text-gray-600">
                        <span>Subtotal (<span x-text="$store.posStore.itemsCount"></span> art.)</span>
                        <span x-text="$store.posStore.formatMoney($store.posStore.subtotal)"></span>
                    </div>
                    <div class="flex items-center justify-between text-base font-bold text-gray-900">
                        <span>Total</span>
                        <span x-text="$store.posStore.formatMoney($store.posStore.total)"></span>
                    </div>
                </div>

                <!-- Cobrar button -->
                <button type="button"
                        @click="$store.posStore.cobrar()"
                        :disabled="!$store.posStore.puedeCobrar || $store.posStore.procesando"
                        :class="!$store.posStore.puedeCobrar || $store.posStore.procesando ? 'cursor-not-allowed bg-gray-300' : 'bg-emerald-500 hover:bg-emerald-600'"
                        class="rounded-2xl px-4 py-3 text-base font-semibold text-white shadow-sm transition">
                    <span x-show="!$store.posStore.procesando" x-text="$store.posStore.metodo === 'fiado' ? 'Registrar fiado' : 'Cobrar'"></span>
                    <span x-show="$store.posStore.procesando" style="display: none;">Procesando...</span>
                </button>
            </aside>

            <!-- Quick-create customer modal -->
            <div x-show="$store.posStore.clienteCreateOpen"
                 class="fixed inset-0 z-50 overflow-y-auto px-4 py-6"
                 style="display: none;">
                <div class="fixed inset-0 bg-gray-500 opacity-75" @click="$store.posStore.closeClienteCreate()"></div>
                <div class="relative mx-auto mb-6 w-full max-w-md rounded-lg bg-white shadow-xl">
                    <form @submit.prevent="$store.posStore.submitClienteCreate()" class="space-y-4 p-6">
                        <h3 class="text-base font-semibold text-gray-900">Nuevo cliente</h3>
                        <div>
                            <label for="cliente-create-name" class="block text-sm font-medium text-gray-700">Nombre</label>
                            <input id="cliente-create-name" type="text" x-model="$store.posStore.clienteCreateForm.name"
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                            <p x-show="$store.posStore.clienteCreateErrors.name" x-text="$store.posStore.clienteCreateErrors.name?.[0]" class="mt-1 text-sm text-red-600"></p>
                        </div>
                        <div>
                            <label for="cliente-create-document" class="block text-sm font-medium text-gray-700">Documento</label>
                            <input id="cliente-create-document" type="text" x-model="$store.posStore.clienteCreateForm.document"
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <p x-show="$store.posStore.clienteCreateErrors.document" x-text="$store.posStore.clienteCreateErrors.document?.[0]" class="mt-1 text-sm text-red-600"></p>
                        </div>
                        <div>
                            <label for="cliente-create-phone" class="block text-sm font-medium text-gray-700">Teléfono</label>
                            <input id="cliente-create-phone" type="text" x-model="$store.posStore.clienteCreateForm.phone"
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <p x-show="$store.posStore.clienteCreateErrors.phone" x-text="$store.posStore.clienteCreateErrors.phone?.[0]" class="mt-1 text-sm text-red-600"></p>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="$store.posStore.closeClienteCreate()"
                                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Cancelar</button>
                            <button type="submit" :disabled="$store.posStore.clienteCreateSaving"
                                    :class="$store.posStore.clienteCreateSaving ? 'cursor-not-allowed bg-gray-300' : 'bg-emerald-600 hover:bg-emerald-700'"
                                    class="rounded-md px-4 py-2 text-sm font-medium text-white transition-colors">
                                <span x-show="!$store.posStore.clienteCreateSaving">Crear cliente</span>
                                <span x-show="$store.posStore.clienteCreateSaving" style="display: none;">Guardando...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
