@php
    $presentationOptions = $presentations->map(function ($presentation) use ($availableBaseUnitsByVariant) {
        $price = $presentation->prices->firstWhere('ends_at', null) ?? $presentation->prices->first();
        $availableBaseUnits = (float) ($availableBaseUnitsByVariant[$presentation->product_variant_id] ?? 0);
        $availableSaleUnits = (float) $presentation->conversion_factor > 0
            ? round($availableBaseUnits / (float) $presentation->conversion_factor, 3)
            : 0;

        return [
            'id' => $presentation->id,
            'label' => $presentation->variant->product->name . ' — ' . $presentation->variant->name . ' — ' . $presentation->name,
            'price' => $price ? number_format($price->price_amount / 100, 2, '.', '') : null,
            'available_sale_units' => number_format($availableSaleUnits, 3, '.', ''),
            'barcode' => $presentation->variant->barcode,
            'internal_code' => $presentation->variant->product->internal_code,
        ];
    })->values();

    $presentationMap = $presentationOptions->keyBy('id');
    $oldItems = collect(old('items', []))->map(function ($item) use ($presentationMap) {
        $selected = filled($item['sale_presentation_id'] ?? null) ? $presentationMap->get((int) $item['sale_presentation_id']) : null;

        return [
            'sale_presentation_id' => $item['sale_presentation_id'] ?? '',
            'quantity' => $item['quantity'] ?? 1,
            'label' => $item['label'] ?? $item['search'] ?? $selected['label'] ?? '',
            'price' => $selected['price'] ?? null,
            'available_sale_units' => $selected['available_sale_units'] ?? '0.000',
        ];
    })->values();

    $oldCustomerId = old('customer_id');
    $oldPaymentMethod = old('payment_method', 'cash');
    $oldMixedCash = old('mixed_payments.cash', '0.00');
    $oldMixedTransfer = old('mixed_payments.transfer', '0.00');
    $oldReceivedAmount = old('received_amount', '');
    $oldAllowCreditSale = old('allow_credit_sale', false);
    $oldConfirmCreditSale = old('confirm_credit_sale', false);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">POS</h2>
                <p class="text-sm text-gray-500">Mostrador rápido para ventas simples, con cobro directo en efectivo o transferencia y escape seguro hacia la venta completa.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('sales.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Historial de ventas</a>
                <a href="{{ route('sales.create') }}" class="rounded-md border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700">Venta completa</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span>{{ session('status') }}</span>
                        @if (session('receipt_sale_id'))
                            <a href="{{ route('sales.show', session('receipt_sale_id')) }}" class="font-medium text-emerald-800 underline">Ver / imprimir comprobante</a>
                        @endif
                    </div>
                </div>
            @endif

            @if ($errors->has('pos'))
                <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('pos') }}</div>
            @endif

            @if (! $currentCashSession)
                <div class="rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    No hay una caja abierta. Puedes preparar la venta en POS, pero para cobrar en efectivo debes abrir caja o continuar en venta completa.
                </div>
            @endif

            <form method="POST" action="{{ route('pos.store') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @csrf
                <input type="hidden" name="action" id="pos-action" value="checkout">
                <input type="hidden" name="payment_method" id="pos-payment-method" value="{{ $oldPaymentMethod }}">
                <input type="hidden" name="allow_credit_sale" id="pos-allow-credit-sale" value="{{ $oldAllowCreditSale ? 1 : 0 }}">

                <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <div class="space-y-6">
                        <section class="rounded-lg border border-gray-200">
                            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                                <p class="text-sm font-semibold text-gray-900">Buscador principal</p>
                                <p class="mt-1 text-xs text-gray-500">Busca por nombre, código o barcode. Si el código coincide exacto, el producto se agrega automáticamente.</p>
                            </div>
                            <div class="p-4">
                                <input id="pos-search" type="text" placeholder="Busca o escanea un producto" class="block w-full rounded-md border-gray-300 shadow-sm" autocomplete="off">
                                <div id="pos-search-results" class="mt-2 hidden overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm"></div>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between bg-gray-50 px-4 py-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Líneas de venta</p>
                                    <p class="mt-1 text-xs text-gray-500">Solo se muestran las líneas activas. Repetir presentación incrementa cantidad.</p>
                                </div>
                                <span id="pos-line-count" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">0 líneas</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50 text-gray-500">
                                        <tr>
                                            <th class="px-4 py-3 text-left">Producto</th>
                                            <th class="px-4 py-3 text-left">Cantidad</th>
                                            <th class="px-4 py-3 text-left">Estado</th>
                                            <th class="px-4 py-3 text-left">Subtotal</th>
                                            <th class="px-4 py-3 text-right">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="pos-lines-body" class="divide-y divide-gray-100 bg-white">
                                        @forelse ($oldItems as $index => $item)
                                            <tr data-index="{{ $index }}" data-presentation-id="{{ $item['sale_presentation_id'] }}">
                                                <td class="px-4 py-3 align-top">
                                                    <input type="hidden" name="items[{{ $index }}][sale_presentation_id]" value="{{ $item['sale_presentation_id'] }}" class="pos-presentation-id">
                                                    <p class="font-medium text-gray-900">{{ $item['label'] }}</p>
                                                    <p class="mt-1 text-xs text-gray-500">Precio actual: ${{ $item['price'] ?? '0.00' }}</p>
                                                </td>
                                                <td class="px-4 py-3 align-top">
                                                    <div class="flex w-fit items-center gap-2 rounded-md border border-gray-200 px-2 py-1">
                                                        <button type="button" class="pos-qty-decrease rounded px-2 py-1 text-sm text-gray-600 hover:bg-gray-100">−</button>
                                                        <input name="items[{{ $index }}][quantity]" type="number" step="0.001" min="0.001" value="{{ $item['quantity'] }}" class="pos-quantity w-20 rounded-md border-gray-300 text-center shadow-sm">
                                                        <button type="button" class="pos-qty-increase rounded px-2 py-1 text-sm text-gray-600 hover:bg-gray-100">+</button>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 align-top">
                                                    <p class="pos-line-state text-sm text-emerald-700">Lista</p>
                                                    <p class="pos-line-state-detail mt-1 text-xs text-gray-500">Disponible aprox.: {{ $item['available_sale_units'] }}</p>
                                                </td>
                                                <td class="px-4 py-3 align-top">
                                                    <p class="pos-line-subtotal font-medium text-gray-900">$0.00</p>
                                                </td>
                                                <td class="px-4 py-3 text-right align-top">
                                                    <button type="button" class="pos-remove-line text-red-600 hover:text-red-800">Quitar</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr id="pos-empty-row">
                                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">Todavía no agregas productos. Empieza buscando o escaneando uno.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @error('items')
                                <div class="border-t border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
                            @enderror
                        </section>
                    </div>

                    <aside x-data class="space-y-4 xl:sticky xl:top-6">
                        @once
                            @php
                                $initialCustomerName = '';
                                $initialCustomerId = null;
                                if ($oldCustomerId !== null) {
                                    $initialCustomerId = (int) $oldCustomerId;
                                    $found = $customers->firstWhere('id', $initialCustomerId);
                                    if ($found) {
                                        $initialCustomerName = $found->name;
                                    }
                                }
                                $initialPayload = [
                                    'creditActive' => $oldAllowCreditSale ? '1' : '0',
                                    'paymentMethod' => $oldPaymentMethod,
                                    'selectedCustomerId' => $initialCustomerId,
                                    'selectedCustomerName' => $initialCustomerName,
                                    'fiadoAutoEnabled' => $fiadoAutoEnabled ? '1' : '0',
                                    'customerQuery' => $initialCustomerName,
                                ];
                                $initialPayloadJson = json_encode($initialPayload, JSON_UNESCAPED_UNICODE);
                            @endphp
                            <script>window.__POS_INITIAL__ = {!! $initialPayloadJson !!};</script>
                        @endonce
                        <section class="rounded-lg border border-gray-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Resumen operativo</p>
                            <div class="mt-3 grid gap-3 text-sm">
                                <div class="rounded-lg bg-white p-3">
                                    <p class="text-gray-500">Cliente actual</p>
                                    <p id="pos-customer-label" class="mt-1 font-semibold text-gray-900" x-text="$store.posSidebar.selectedCustomerName || 'Anónimo'">{{ $oldCustomerId ? $customers->firstWhere('id', (int) $oldCustomerId)?->name : 'Anónimo' }}</p>
                                </div>
                                <div class="rounded-lg bg-white p-3">
                                    <p class="text-gray-500">Método actual</p>
                                    <p id="pos-payment-method-label" class="mt-1 font-semibold text-gray-900" x-text="$store.posSidebar.paymentMethod === 'transfer' ? 'Transferencia' : ($store.posSidebar.paymentMethod === 'mixed' ? 'Mixto' : 'Efectivo')">{{ $oldPaymentMethod === 'transfer' ? 'Transferencia' : ($oldPaymentMethod === 'mixed' ? 'Mixto' : 'Efectivo') }}</p>
                                    <p id="pos-payment-method-caption" class="mt-1 text-xs text-gray-500">En esta fase ya puedes usar mixto, pero debe cuadrar exactamente con el total.</p>
                                    <div id="pos-payment-breakdown" class="{{ $oldPaymentMethod === 'mixed' ? '' : 'hidden' }} mt-2 rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-700">
                                        <p>Efectivo: <span id="pos-breakdown-cash">${{ number_format((float) $oldMixedCash, 2, '.', '') }}</span></p>
                                        <p class="mt-1">Transferencia: <span id="pos-breakdown-transfer">${{ number_format((float) $oldMixedTransfer, 2, '.', '') }}</span></p>
                                    </div>
                                </div>
                                <div class="rounded-lg bg-white p-3">
                                    <p class="text-gray-500">Total</p>
                                    <p id="pos-total" class="mt-1 text-2xl font-semibold text-gray-900">$0.00</p>
                                    <div id="pos-credit-summary" class="{{ $oldAllowCreditSale ? '' : 'hidden' }} mt-3 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                        <p>Pagado: <span id="pos-credit-paid">$0.00</span></p>
                                        <p class="mt-1">Saldo pendiente: <span id="pos-credit-pending">$0.00</span></p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <button type="button" @click="$store.posSidebar.togglePanel('customer')" :class="$store.posSidebar.isButtonActive('customer') ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-gray-300 bg-white text-gray-700'" class="rounded-md border px-3 py-2 text-sm font-medium">
                                    Asignar cliente
                                    <span @click.stop="$store.posSidebar.togglePin('customer')" x-show="$store.posSidebar.activePanel === 'customer' || $store.posSidebar.pinnedPanels.includes('customer')" class="ml-1.5 inline-flex items-center">
                                        <svg :class="$store.posSidebar.pinnedPanels.includes('customer') ? 'text-amber-500' : 'text-gray-400'" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v18l7-5 7 5V3z"/>
                                        </svg>
                                    </span>
                                </button>
                                <button type="button" @click="$store.posSidebar.togglePanel('payment')" :class="$store.posSidebar.isButtonActive('payment') ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-gray-300 bg-white text-gray-700'" class="rounded-md border px-3 py-2 text-sm font-medium">
                                    Cambiar método
                                    <span @click.stop="$store.posSidebar.togglePin('payment')" x-show="$store.posSidebar.activePanel === 'payment' || $store.posSidebar.pinnedPanels.includes('payment')" class="ml-1.5 inline-flex items-center">
                                        <svg :class="$store.posSidebar.pinnedPanels.includes('payment') ? 'text-amber-500' : 'text-gray-400'" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v18l7-5 7 5V3z"/>
                                        </svg>
                                    </span>
                                </button>
                                <button type="button" @click="$store.posSidebar.togglePanel('received')" x-show="$store.posSidebar.paymentMethod === 'cash'" :class="($store.posSidebar.activePanel === 'received' || $store.posSidebar.pinnedPanels.includes('received')) ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-gray-300 bg-white text-gray-700'" class="rounded-md border px-3 py-2 text-sm font-medium">
                                    Ingresar monto recibido
                                    <span @click.stop="$store.posSidebar.togglePin('received')" x-show="$store.posSidebar.activePanel === 'received' || $store.posSidebar.pinnedPanels.includes('received')" class="ml-1.5 inline-flex items-center">
                                        <svg :class="$store.posSidebar.pinnedPanels.includes('received') ? 'text-amber-500' : 'text-gray-400'" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v18l7-5 7 5V3z"/>
                                        </svg>
                                    </span>
                                </button>
                                <button type="button" @click="$store.posSidebar.handleCreditToggle()" x-show="$store.posSidebar.paymentMethod === 'cash'" :class="$store.posSidebar.creditActive ? 'border-amber-500 bg-amber-100 text-amber-900' : ($store.posSidebar.pinnedPanels.includes('credit') ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-amber-300 bg-amber-50 text-amber-800')" class="rounded-md border px-3 py-2 text-sm font-medium">
                                    <span x-text="$store.posSidebar.creditActive ? 'Fiado activado' : 'Convertir a fiado'"></span>
                                    <span @click.stop="$store.posSidebar.togglePin('credit')" x-show="$store.posSidebar.creditActive || $store.posSidebar.pinnedPanels.includes('credit')" class="ml-1.5 inline-flex items-center">
                                        <svg :class="$store.posSidebar.pinnedPanels.includes('credit') ? 'text-amber-500' : 'text-gray-400'" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v18l7-5 7 5V3z"/>
                                        </svg>
                                    </span>
                                </button>
                                <button type="button" id="continue-complete-sale" class="rounded-md border border-indigo-300 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700">Continuar en venta completa</button>
                            </div>

                            <div id="pos-customer-panel" x-show="$store.posSidebar.activePanel === 'customer' || $store.posSidebar.pinnedPanels.includes('customer')" class="mt-4 rounded-lg border border-gray-200 bg-white p-3">
                                <label class="block text-sm font-medium text-gray-700">Cliente</label>
                                <p class="mt-1 text-xs text-gray-500">Escribí para buscar clientes por nombre o teléfono.</p>

                                <div class="relative mt-2">
                                    <div class="flex gap-2">
                                        <input type="text"
                                               x-model="$store.posSidebar.customerQuery"
                                               @input.debounce.300ms="$store.posSidebar.searchCustomers()"
                                               @keydown.arrow-down.prevent="$store.posSidebar.customerHighlightIndex = Math.min($store.posSidebar.customerHighlightIndex + 1, $store.posSidebar.customerResults.length - 1)"
                                               @keydown.arrow-up.prevent="$store.posSidebar.customerHighlightIndex = Math.max($store.posSidebar.customerHighlightIndex - 1, -1)"
                                               @keydown.enter.prevent="if ($store.posSidebar.customerHighlightIndex >= 0) $store.posSidebar.selectCustomer($store.posSidebar.customerResults[$store.posSidebar.customerHighlightIndex])"
                                               @keydown.escape.prevent="$store.posSidebar.customerResults = []; $store.posSidebar.customerHighlightIndex = -1"
                                               placeholder="Buscá por nombre o teléfono..."
                                               class="block w-full rounded-md border-gray-300 shadow-sm">

                                        <button type="button"
                                                @click="$store.posSidebar.clearCustomer()"
                                                x-show="$store.posSidebar.selectedCustomerId"
                                                class="shrink-0 rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">
                                            Limpiar
                                        </button>
                                    </div>

                                    <div x-show="$store.posSidebar.customerLoading" class="mt-1 text-xs text-gray-500">Buscando…</div>

                                    <div x-show="$store.posSidebar.customerResults.length > 0"
                                         class="absolute z-10 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg max-h-60 overflow-y-auto">
                                        <template x-for="(customer, index) in $store.posSidebar.customerResults" :key="customer.id">
                                            <button type="button"
                                                    @click="$store.posSidebar.selectCustomer(customer)"
                                                    :class="index === $store.posSidebar.customerHighlightIndex ? 'bg-indigo-50 text-indigo-900' : 'text-gray-900 hover:bg-gray-50'"
                                                    class="flex w-full items-center justify-between px-3 py-2 text-sm">
                                                <span>
                                                    <span class="block font-medium" x-text="customer.name"></span>
                                                    <span class="block text-xs text-gray-500" x-text="customer.phone ? 'Tel: ' + customer.phone : ''"></span>
                                                </span>
                                            </button>
                                        </template>
                                    </div>

                                    <div x-show="$store.posSidebar.customerQuery.trim().length > 0 && $store.posSidebar.customerResults.length === 0 && !$store.posSidebar.customerLoading"
                                         class="mt-1 text-xs text-gray-500">
                                        No se encontraron clientes.
                                    </div>
                                </div>

                                <div x-show="$store.posSidebar.selectedCustomerId" class="mt-3 rounded-md bg-green-50 px-3 py-2 text-sm text-green-800">
                                    Cliente seleccionado: <strong x-text="$store.posSidebar.selectedCustomerName"></strong>
                                </div>

                                <input type="hidden" name="customer_id" x-model="$store.posSidebar.selectedCustomerId">
                                <p id="pos-customer-inline-error" class="hidden mt-2 text-xs text-red-600"></p>
                                @error('customer_id')
                                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="pos-payment-methods-panel" x-show="$store.posSidebar.activePanel === 'payment' || $store.posSidebar.pinnedPanels.includes('payment')" class="mt-4 rounded-lg border border-gray-200 bg-white p-3">
                                <p class="text-sm font-medium text-gray-700">Método de pago</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button type="button" data-payment-method="cash" class="pos-payment-choice rounded-md border px-3 py-2 text-sm font-medium {{ $oldPaymentMethod === 'cash' ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-gray-300 bg-white text-gray-700' }}">Efectivo</button>
                                    <button type="button" data-payment-method="transfer" class="pos-payment-choice rounded-md border px-3 py-2 text-sm font-medium {{ $oldPaymentMethod === 'transfer' ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-gray-300 bg-white text-gray-700' }}">Transferencia</button>
                                    <button type="button" data-payment-method="mixed" class="pos-payment-choice rounded-md border px-3 py-2 text-sm font-medium {{ $oldPaymentMethod === 'mixed' ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-gray-300 bg-white text-gray-700' }}">Mixto</button>
                                </div>

                                <div id="pos-mixed-panel" class="{{ $oldPaymentMethod === 'mixed' ? '' : 'hidden' }} mt-4 rounded-lg border border-gray-200 bg-slate-50 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Desglose mixto</p>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label for="pos-mixed-cash" class="block text-sm font-medium text-gray-700">Efectivo</label>
                                            <input id="pos-mixed-cash" name="mixed_payments[cash]" type="number" step="0.01" min="0" value="{{ $oldMixedCash }}" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm">
                                        </div>
                                        <div>
                                            <label for="pos-mixed-transfer" class="block text-sm font-medium text-gray-700">Transferencia</label>
                                            <input id="pos-mixed-transfer" name="mixed_payments[transfer]" type="number" step="0.01" min="0" value="{{ $oldMixedTransfer }}" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm">
                                        </div>
                                    </div>
                                    <p id="pos-mixed-inline-error" class="hidden mt-3 text-xs text-red-600"></p>
                                    @error('mixed_payments')
                                        <p class="mt-3 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div id="pos-received-panel" x-show="$store.posSidebar.activePanel === 'received' || $store.posSidebar.pinnedPanels.includes('received')" class="mt-4 rounded-lg border border-gray-200 bg-white p-3">
                                <label for="pos-received-amount" class="block text-sm font-medium text-gray-700">Recibido</label>
                                <input id="pos-received-amount" name="received_amount" type="number" step="0.01" min="0" value="{{ $oldReceivedAmount }}" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm">
                                <p id="pos-received-inline-error" class="hidden mt-3 text-xs text-red-600"></p>
                                @error('received_amount')
                                    <p class="mt-3 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                <p id="pos-change-preview" class="{{ $oldReceivedAmount !== '' ? '' : 'hidden' }} mt-3 text-xs text-emerald-700"></p>
                            </div>

                            <div id="pos-credit-panel" x-show="$store.posSidebar.activePanel === 'credit' || $store.posSidebar.pinnedPanels.includes('credit')" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-medium text-amber-900">Confirmar saldo pendiente</p>
                                        <p class="mt-1 text-xs text-amber-800">POS solo permite fiado desde efectivo y con cliente obligatorio.</p>
                                    </div>
                                    <button type="button" id="cancel-credit-sale" class="text-xs font-medium text-amber-700">Quitar fiado</button>
                                </div>
                                <div class="mt-3 rounded-md bg-white px-3 py-2 text-xs text-gray-700">
                                    <p>Total: <span id="pos-credit-total">$0.00</span></p>
                                    <p class="mt-1">Pagado: <span id="pos-credit-confirm-paid">$0.00</span></p>
                                    <p class="mt-1">Saldo pendiente: <span id="pos-credit-confirm-pending">$0.00</span></p>
                                </div>
                                <label class="mt-3 flex items-start gap-2 text-sm text-amber-900">
                                    <input type="checkbox" name="confirm_credit_sale" id="pos-confirm-credit-sale" value="1" @checked($oldConfirmCreditSale) class="mt-1 rounded border-gray-300 text-amber-600 shadow-sm">
                                    <span>Confirmo que esta venta debe cerrarse con abono parcial y saldo pendiente a nombre del cliente seleccionado.</span>
                                </label>
                                @error('credit_sale')
                                    <p class="mt-3 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </section>

                        <section class="rounded-lg border border-gray-200 bg-white p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Fase 2 parcial</p>
                            <ul class="mt-3 space-y-2 text-sm text-gray-600">
                                <li>• Puedes cobrar en efectivo, transferencia o mixto.</li>
                                <li>• Toda venta cobrada desde POS sigue requiriendo caja abierta.</li>
                                <li>• En efectivo puedes activar apoyo de vuelto sin cambiar la regla contable de la venta.</li>
                                <li>• El fiado desde POS solo nace desde efectivo, con cliente y confirmación explícita.</li>
                                <li>• Si aparece stock insuficiente o costo pendiente, derivamos a venta completa.</li>
                                <li>• En pago mixto, efectivo + transferencia debe cuadrar exactamente.</li>
                            </ul>
                        </section>

                        <div class="flex flex-wrap justify-end gap-3">
                            <a href="{{ route('sales.index') }}" class="text-sm text-gray-600">Cancelar</a>
                            <button type="submit" id="pos-checkout-button" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">{{ $oldPaymentMethod === 'transfer' ? 'Cobrar transferencia' : 'Cobrar efectivo' }}</button>
                        </div>
                    </aside>
                </div>
            </form>
        </div>
    </div>

    <script type="application/json" id="pos-presentation-options">@json($presentationOptions)</script>
    <script type="application/json" id="pos-old-items">@json($oldItems)</script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const options = JSON.parse(document.getElementById('pos-presentation-options').textContent);
            const oldItems = JSON.parse(document.getElementById('pos-old-items').textContent);
            const searchUrl = "{{ route('sales.search') }}";
            const body = document.getElementById('pos-lines-body');
            const emptyRowId = 'pos-empty-row';
            const searchInput = document.getElementById('pos-search');
            const resultsContainer = document.getElementById('pos-search-results');
            const totalLabel = document.getElementById('pos-total');
            const lineCountLabel = document.getElementById('pos-line-count');
            const customerInlineError = document.getElementById('pos-customer-inline-error');
            const paymentMethodInput = document.getElementById('pos-payment-method');
            const paymentMethodLabel = document.getElementById('pos-payment-method-label');
            const paymentMethodCaption = document.getElementById('pos-payment-method-caption');
            const paymentBreakdown = document.getElementById('pos-payment-breakdown');
            const breakdownCash = document.getElementById('pos-breakdown-cash');
            const breakdownTransfer = document.getElementById('pos-breakdown-transfer');
            const paymentMethodToggle = document.getElementById('toggle-payment-methods');
            const paymentMethodsPanel = document.getElementById('pos-payment-methods-panel');
            const paymentChoices = Array.from(document.querySelectorAll('.pos-payment-choice'));
            const mixedPanel = document.getElementById('pos-mixed-panel');
            const mixedCashInput = document.getElementById('pos-mixed-cash');
            const mixedTransferInput = document.getElementById('pos-mixed-transfer');
            const mixedInlineError = document.getElementById('pos-mixed-inline-error');
            const receivedToggle = document.getElementById('toggle-received-amount');
            const receivedPanel = document.getElementById('pos-received-panel');
            const receivedAmountInput = document.getElementById('pos-received-amount');
            const receivedInlineError = document.getElementById('pos-received-inline-error');
            const changePreview = document.getElementById('pos-change-preview');
            const allowCreditSaleInput = document.getElementById('pos-allow-credit-sale');
            const creditToggle = document.getElementById('toggle-credit-sale');
            const creditPanel = document.getElementById('pos-credit-panel');
            const creditSummary = document.getElementById('pos-credit-summary');
            const creditPaid = document.getElementById('pos-credit-paid');
            const creditPending = document.getElementById('pos-credit-pending');
            const creditConfirmPaid = document.getElementById('pos-credit-confirm-paid');
            const creditConfirmPending = document.getElementById('pos-credit-confirm-pending');
            const creditTotal = document.getElementById('pos-credit-total');
            const cancelCreditSale = document.getElementById('cancel-credit-sale');
            const confirmCreditSale = document.getElementById('pos-confirm-credit-sale');
            const checkoutButton = document.getElementById('pos-checkout-button');
            const actionInput = document.getElementById('pos-action');
            const continueCompleteButton = document.getElementById('continue-complete-sale');
            let nextIndex = oldItems.length;
            let debounceTimer;

            function formatMoney(value) {
                return `$${Number(value).toFixed(2)}`;
            }

            function hideResults() {
                resultsContainer.classList.add('hidden');
                resultsContainer.innerHTML = '';
            }

            function findOptionById(id) {
                return options.find((option) => String(option.id) === String(id));
            }

            function getLineRows() {
                return Array.from(body.querySelectorAll('tr[data-index]'));
            }

            function updateEmptyState() {
                const hasRows = getLineRows().length > 0;
                const existingEmpty = document.getElementById(emptyRowId);

                if (!hasRows && !existingEmpty) {
                    body.insertAdjacentHTML('beforeend', `<tr id="${emptyRowId}"><td colspan="5" class="px-4 py-8 text-center text-gray-500">Todavía no agregas productos. Empieza buscando o escaneando uno.</td></tr>`);
                }

                if (hasRows && existingEmpty) {
                    existingEmpty.remove();
                }

                lineCountLabel.textContent = `${getLineRows().length} ${getLineRows().length === 1 ? 'línea' : 'líneas'}`;
            }

            function updateCustomerLabel() {
                // Handled by Alpine x-text binding on the label element
            }

            function clearCustomerInlineError() {
                customerInlineError.classList.add('hidden');
                customerInlineError.textContent = '';
            }

            function getAlpineData() {
                return window.Alpine?.store('posSidebar') ?? null;
            }

            function requireCustomerForCredit(message = 'Debes seleccionar un cliente para registrar fiado desde POS.') {
                customerInlineError.textContent = message;
                customerInlineError.classList.remove('hidden');

                const alpineData = getAlpineData();
                if (alpineData) {
                    alpineData.activePanel = 'customer';
                    // Focus the typeahead input inside the customer panel
                    const input = document.querySelector('#pos-customer-panel input[type="text"]');
                    if (input) input.focus();
                }
            }

            function updateCreditToggleUi() {
                // Alpine handles the credit toggle button state via :class and x-text
            }

            function activateCreditSale({ requireCustomer = true } = {}) {
                const alpineData = getAlpineData();
                const hasCustomer = alpineData?.selectedCustomerId ?? false;

                if (requireCustomer && !hasCustomer) {
                    requireCustomerForCredit();
                    return false;
                }

                allowCreditSaleInput.value = '1';
                updateCreditToggleUi();
                updateCreditSummary();

                if (alpineData) {
                    alpineData.creditActive = true;
                    alpineData.activePanel = 'credit';
                    alpineData.syncToHiddenInputs();
                }

                return true;
            }

            function updatePaymentMethodUi() {
                const method = ['cash', 'transfer', 'mixed'].includes(paymentMethodInput.value)
                    ? paymentMethodInput.value
                    : 'cash';

                paymentMethodLabel.textContent = method === 'transfer'
                    ? 'Transferencia'
                    : (method === 'mixed' ? 'Mixto' : 'Efectivo');
                paymentMethodCaption.textContent = method === 'mixed'
                    ? 'El cobro debe cuadrar exactamente entre efectivo y transferencia.'
                    : 'Caja abierta sigue siendo obligatoria para registrar pagos desde POS.';
                checkoutButton.textContent = method === 'transfer'
                    ? 'Cobrar transferencia'
                    : (method === 'mixed' ? 'Cobrar pago mixto' : 'Cobrar efectivo');
                mixedPanel.classList.toggle('hidden', method !== 'mixed');
                paymentBreakdown.classList.toggle('hidden', method !== 'mixed');

                if (method !== 'mixed') {
                    mixedCashInput.value = '0.00';
                    mixedTransferInput.value = '0.00';
                    mixedInlineError.classList.add('hidden');
                    mixedInlineError.textContent = '';
                }

                if (method !== 'cash') {
                    clearReceivedAmount();
                    clearCreditSale();
                }

                const alpineData = window.Alpine?.store('posSidebar');
                if (alpineData) {
                    alpineData.paymentMethod = method;
                    if (method !== 'cash' && alpineData.activePanel === 'received' || alpineData.activePanel === 'credit') {
                        alpineData.activePanel = null;
                    }
                    alpineData.syncToHiddenInputs();
                }

                updateMixedBreakdown();
                updateReceivedFeedback();
                updateCreditSummary();
                updateCreditToggleUi();

                paymentChoices.forEach((button) => {
                    const active = button.dataset.paymentMethod === method;
                    button.className = `pos-payment-choice rounded-md border px-3 py-2 text-sm font-medium ${active
                        ? 'border-indigo-300 bg-indigo-50 text-indigo-700'
                        : 'border-gray-300 bg-white text-gray-700'}`;
                });
            }

            function updateMixedBreakdown() {
                breakdownCash.textContent = formatMoney(Number(mixedCashInput?.value || 0));
                breakdownTransfer.textContent = formatMoney(Number(mixedTransferInput?.value || 0));
            }

            function getCurrentTotal() {
                return Number(totalLabel.textContent.replace(/[^0-9.-]+/g, ''));
            }

            function clearReceivedAmount() {
                receivedAmountInput.value = '';
                receivedInlineError.classList.add('hidden');
                receivedInlineError.textContent = '';
                changePreview.classList.add('hidden');
                changePreview.textContent = '';
                updateCreditSummary();
            }

            function clearCreditSale() {
                allowCreditSaleInput.value = '0';
                confirmCreditSale.checked = false;
                creditSummary.classList.add('hidden');
                updateCreditToggleUi();

                const alpineData = getAlpineData();
                if (alpineData) {
                    alpineData.creditActive = false;
                    if (alpineData.activePanel === 'credit') alpineData.activePanel = null;
                    alpineData.syncToHiddenInputs();
                }
            }

            function getReceivedAmountValue() {
                if (!receivedAmountInput.value) {
                    return allowCreditSaleInput.value === '1' ? 0 : null;
                }

                return Number(receivedAmountInput.value || 0);
            }

            function updateCreditSummary() {
                const total = getCurrentTotal();
                const received = getReceivedAmountValue();
                const paid = Math.min(received ?? total, total);
                const pending = Math.max(total - paid, 0);
                const active = paymentMethodInput.value === 'cash' && allowCreditSaleInput.value === '1';

                creditTotal.textContent = formatMoney(total);
                creditPaid.textContent = formatMoney(paid);
                creditPending.textContent = formatMoney(pending);
                creditConfirmPaid.textContent = formatMoney(paid);
                creditConfirmPending.textContent = formatMoney(pending);
                creditSummary.classList.toggle('hidden', !active || pending <= 0);
                creditPanel.classList.toggle('hidden', !active);
                updateCreditToggleUi();
            }

            function updateReceivedFeedback() {
                const method = paymentMethodInput.value;
                const received = getReceivedAmountValue();
                const total = getCurrentTotal();

                if (method !== 'cash' || received === null) {
                    changePreview.classList.add('hidden');
                    changePreview.textContent = '';
                    updateCreditSummary();
                    return;
                }

                if (received < total) {
                    changePreview.classList.add('hidden');
                    changePreview.textContent = '';
                    updateCreditSummary();
                    return;
                }

                const change = received - total;

                if (change > 0) {
                    changePreview.textContent = `Vuelto: ${formatMoney(change)}`;
                    changePreview.classList.remove('hidden');
                    return;
                }

                changePreview.classList.add('hidden');
                changePreview.textContent = '';
                updateCreditSummary();
            }

            function updateLineState(row, option, quantity) {
                const state = row.querySelector('.pos-line-state');
                const detail = row.querySelector('.pos-line-state-detail');
                const available = Number(option?.available_sale_units || 0);
                const hasWarning = quantity > available || available <= 0;

                if (hasWarning) {
                    state.className = 'pos-line-state text-sm text-amber-700';
                    state.textContent = 'Requiere venta completa';
                    detail.textContent = available <= 0
                        ? 'No hay stock/costo suficiente. Derivar a venta completa.'
                        : `Stock aprox. ${option.available_sale_units}. Esta cantidad debe revisarse en venta completa.`;
                    return;
                }

                state.className = 'pos-line-state text-sm text-emerald-700';
                state.textContent = 'Lista';
                detail.textContent = `Disponible aprox.: ${option?.available_sale_units ?? '0.000'}`;
            }

            function refreshSummary() {
                let total = 0;

                getLineRows().forEach((row) => {
                    const presentationId = row.dataset.presentationId;
                    const option = findOptionById(presentationId);
                    const quantity = Number(row.querySelector('.pos-quantity').value || 0);
                    const price = Number(option?.price || 0);
                    const subtotal = price * quantity;

                    row.querySelector('.pos-line-subtotal').textContent = formatMoney(subtotal);
                    updateLineState(row, option, quantity);
                    total += subtotal;
                });

                totalLabel.textContent = formatMoney(total);
                updateEmptyState();
                updateCustomerLabel();
                updateMixedBreakdown();
                updateReceivedFeedback();
                updateCreditSummary();
            }

            function bindRowEvents(row) {
                row.querySelector('.pos-qty-decrease').addEventListener('click', () => {
                    const input = row.querySelector('.pos-quantity');
                    const current = Number(input.value || 0);
                    input.value = Math.max(current - 1, 0.001).toFixed(3).replace(/\.000$/, '');
                    refreshSummary();
                });

                row.querySelector('.pos-qty-increase').addEventListener('click', () => {
                    const input = row.querySelector('.pos-quantity');
                    const current = Number(input.value || 0);
                    input.value = (current + 1).toFixed(3).replace(/\.000$/, '');
                    refreshSummary();
                });

                row.querySelector('.pos-quantity').addEventListener('input', refreshSummary);

                row.querySelector('.pos-remove-line').addEventListener('click', () => {
                    row.remove();
                    refreshSummary();
                });
            }

            function renderLine(option, quantity = 1) {
                const index = nextIndex++;
                const html = `
                    <tr data-index="${index}" data-presentation-id="${option.id}">
                        <td class="px-4 py-3 align-top">
                            <input type="hidden" name="items[${index}][sale_presentation_id]" value="${option.id}" class="pos-presentation-id">
                            <p class="font-medium text-gray-900">${option.label}</p>
                            <p class="mt-1 text-xs text-gray-500">Precio actual: $${option.price ?? '0.00'}</p>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="flex w-fit items-center gap-2 rounded-md border border-gray-200 px-2 py-1">
                                <button type="button" class="pos-qty-decrease rounded px-2 py-1 text-sm text-gray-600 hover:bg-gray-100">−</button>
                                <input name="items[${index}][quantity]" type="number" step="0.001" min="0.001" value="${quantity}" class="pos-quantity w-20 rounded-md border-gray-300 text-center shadow-sm">
                                <button type="button" class="pos-qty-increase rounded px-2 py-1 text-sm text-gray-600 hover:bg-gray-100">+</button>
                            </div>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <p class="pos-line-state text-sm text-emerald-700">Lista</p>
                            <p class="pos-line-state-detail mt-1 text-xs text-gray-500">Disponible aprox.: ${option.available_sale_units}</p>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <p class="pos-line-subtotal font-medium text-gray-900">$0.00</p>
                        </td>
                        <td class="px-4 py-3 text-right align-top">
                            <button type="button" class="pos-remove-line text-red-600 hover:text-red-800">Quitar</button>
                        </td>
                    </tr>`;

                body.insertAdjacentHTML('beforeend', html);
                const row = body.querySelector(`tr[data-index="${index}"]`);
                bindRowEvents(row);
                refreshSummary();
            }

            function addOrIncrement(option) {
                const existingRow = getLineRows().find((row) => row.dataset.presentationId === String(option.id));

                if (existingRow) {
                    const input = existingRow.querySelector('.pos-quantity');
                    input.value = (Number(input.value || 0) + 1).toFixed(3).replace(/\.000$/, '');
                    refreshSummary();
                    return;
                }

                renderLine(option, 1);
            }

            function renderResults(results) {
                if (!results.length) {
                    resultsContainer.innerHTML = '<div class="px-3 py-2 text-xs text-gray-500">Sin resultados</div>';
                    resultsContainer.classList.remove('hidden');
                    return;
                }

                resultsContainer.innerHTML = results.map((result) => `
                    <button type="button" class="pos-result-item flex w-full items-start justify-between gap-3 px-3 py-2 text-left hover:bg-gray-50" data-id="${result.id}">
                        <span>
                            <span class="block text-sm font-medium text-gray-800">${result.label}</span>
                            <span class="mt-1 block text-xs text-gray-500">Código: ${result.internal_code ?? '—'} · Barcode: ${result.barcode ?? '—'} · Stock aprox.: ${result.available_sale_units}</span>
                        </span>
                        <span class="text-sm font-medium text-gray-700">${result.price ? `$${result.price}` : '—'}</span>
                    </button>
                `).join('');

                resultsContainer.classList.remove('hidden');

                resultsContainer.querySelectorAll('.pos-result-item').forEach((button) => {
                    button.addEventListener('click', () => {
                        const selected = results.find((result) => String(result.id) === button.dataset.id);
                        addOrIncrement(selected);
                        searchInput.value = '';
                        hideResults();
                        searchInput.focus();
                    });
                });
            }

            async function searchProducts(query) {
                const response = await fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    hideResults();
                    return;
                }

                const data = await response.json();
                const results = data.results ?? [];
                const exactMatch = results.find((result) => result.exact_code_match);

                if (data.auto_select && exactMatch) {
                    addOrIncrement(exactMatch);
                    searchInput.value = '';
                    hideResults();
                    return;
                }

                renderResults(results);
            }

            searchInput.addEventListener('input', () => {
                const query = searchInput.value.trim();
                window.clearTimeout(debounceTimer);

                if (query.length < 1) {
                    hideResults();
                    return;
                }

                debounceTimer = window.setTimeout(() => searchProducts(query), 180);
            });

            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    hideResults();
                }
            });

            document.addEventListener('click', (event) => {
                if (!resultsContainer.contains(event.target) && event.target !== searchInput) {
                    hideResults();
                }
            });

            cancelCreditSale?.addEventListener('click', () => {
                clearCreditSale();
                updateCreditSummary();

                const store = window.Alpine?.store('posSidebar');
                if (store) {
                    store.creditActive = false;
                    store.activePanel = store.activePanel === 'credit' ? null : store.activePanel;
                    store.syncToHiddenInputs();
                }
            });

            paymentChoices.forEach((button) => {
                button.addEventListener('click', () => {
                    paymentMethodInput.value = button.dataset.paymentMethod;
                    updatePaymentMethodUi();
                });
            });

            [mixedCashInput, mixedTransferInput].forEach((input) => {
                input?.addEventListener('input', () => {
                    updateMixedBreakdown();
                    mixedInlineError.classList.add('hidden');
                    mixedInlineError.textContent = '';
                });
            });

            receivedAmountInput?.addEventListener('input', () => {
                receivedInlineError.classList.add('hidden');
                receivedInlineError.textContent = '';
                clearCustomerInlineError();

                const total = getCurrentTotal();
                const received = Number(receivedAmountInput.value || 0);

                const alpineData = window.Alpine?.store('posSidebar');
                const fiadoEnabled = alpineData?.fiadoAutoEnabled ?? true;

                if (paymentMethodInput.value === 'cash' && receivedAmountInput.value !== '' && received < total) {
                    if (!fiadoEnabled) {
                        receivedInlineError.textContent = 'El monto recibido es menor al total y el fiado automático está desactivado en configuración.';
                        receivedInlineError.classList.remove('hidden');
                        if (alpineData) alpineData.activePanel = 'received';
                    } else if (!alpineData?.selectedCustomerId) {
                        requireCustomerForCredit('Para dejar saldo pendiente debes seleccionar un cliente.');
                    } else {
                        activateCreditSale({ requireCustomer: false });
                    }
                }

                if (paymentMethodInput.value === 'cash' && receivedAmountInput.value !== '' && received >= total) {
                    clearCreditSale();
                }

                updateReceivedFeedback();
            });

            continueCompleteButton.addEventListener('click', () => {
                actionInput.value = 'complete';
                continueCompleteButton.closest('form').submit();
            });

            continueCompleteButton.closest('form').addEventListener('submit', (event) => {
                const submitter = event.submitter;
                const alpineData = getAlpineData();

                if (submitter && submitter.id === 'pos-checkout-button') {
                    actionInput.value = 'checkout';

                    if (paymentMethodInput.value === 'mixed') {
                        const total = getCurrentTotal();
                        const mixedTotal = Number(mixedCashInput.value || 0) + Number(mixedTransferInput.value || 0);

                        if (Math.abs(mixedTotal - total) > 0.0001) {
                            event.preventDefault();
                            mixedInlineError.textContent = 'En pago mixto, efectivo + transferencia debe cuadrar exactamente con el total.';
                            mixedInlineError.classList.remove('hidden');
                            if (alpineData) alpineData.activePanel = 'payment';
                        }
                    }

                    if (paymentMethodInput.value === 'cash') {
                        const total = getCurrentTotal();
                        const received = getReceivedAmountValue();

                        if (received !== null && received < total && allowCreditSaleInput.value !== '1') {
                            event.preventDefault();
                            receivedInlineError.textContent = 'El monto recibido es menor al total. Activa “Convertir a fiado” si esa diferencia debe quedar pendiente.';
                            receivedInlineError.classList.remove('hidden');
                            if (alpineData) alpineData.activePanel = 'received';
                        }

                        if (allowCreditSaleInput.value === '1') {
                            const alpineData = getAlpineData();
                            if (!alpineData?.selectedCustomerId) {
                                event.preventDefault();
                                requireCustomerForCredit();
                            }

                            if (!confirmCreditSale.checked) {
                                event.preventDefault();
                                creditPanel.classList.remove('hidden');
                            }
                        }
                    }
                }
            });

            getLineRows().forEach(bindRowEvents);
            refreshSummary();
            updatePaymentMethodUi();
            updateCreditToggleUi();
            searchInput.focus();
        });
    </script>
</x-app-layout>
