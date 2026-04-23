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
            'min_price' => $price && $price->min_price_amount !== null ? number_format($price->min_price_amount / 100, 2, '.', '') : null,
            'barcode' => $presentation->variant->barcode,
            'internal_code' => $presentation->variant->product->internal_code,
            'available_sale_units' => number_format($availableSaleUnits, 3, '.', ''),
        ];
    })->values();

    $presentationMap = $presentationOptions->keyBy('id');
    $oldItems = old('items', [['sale_presentation_id' => '', 'quantity' => 1, 'search' => '', 'manual_unit_price' => '', 'manual_price_reason' => '']]);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Nueva venta</h2>
                <p class="text-sm text-gray-500">Prepara la venta con resumen operativo visible, señales claras y accesos rápidos al núcleo relacionado.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('cash.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Ver caja actual</a>
                <a href="{{ route('receivables.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Ver fiados</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (! $currentCashSession)
                <div class="mb-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    No hay una caja abierta. Puedes preparar la venta, pero no podrás registrar pagos hasta abrir caja.
                </div>
            @endif

            <form method="POST" action="{{ route('sales.store') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @csrf

                <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <div class="order-2 space-y-6 xl:order-1">
                        <div class="grid gap-6 md:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha</label>
                                <input name="sold_at" type="datetime-local" value="{{ old('sold_at', now()->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Cliente</label>
                                <select id="sale-customer-id" name="customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm transition">
                                    <option value="">Venta anónima</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                <p id="sale-customer-feedback" class="mt-1 text-xs text-gray-500">Selecciona cliente solo si habrá saldo a fiar o si quieres trazabilidad nominal.</p>
                                @error('customer_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Notas</label>
                                <input name="notes" type="text" value="{{ old('notes') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between bg-gray-50 px-4 py-3">
                                <div>
                                    <h3 class="font-medium text-gray-800">Líneas de venta</h3>
                                    <p class="mt-1 text-xs text-gray-500">Busca por nombre, código o barcode; revisa por línea override y señales probables antes de guardar.</p>
                                </div>
                                <button type="button" id="add-sale-item" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white">Agregar línea</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm" id="sale-items-table">
                                    <thead class="bg-gray-50 text-gray-500">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Presentación</th>
                                            <th class="px-3 py-2 text-left">Cantidad</th>
                                            <th class="px-3 py-2 text-left">Precio vigente</th>
                                            <th class="px-3 py-2 text-left">Precio manual</th>
                                            <th class="px-3 py-2 text-left">Motivo override</th>
                                            <th class="px-3 py-2 text-right">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($oldItems as $index => $item)
                                            @php($selectedPresentation = filled($item['sale_presentation_id'] ?? null) ? $presentationMap->get((int) $item['sale_presentation_id']) : null)
                                            <tr>
                                                <td class="px-3 py-2 align-top">
                                                    <input type="hidden" name="items[{{ $index }}][sale_presentation_id]" value="{{ $item['sale_presentation_id'] ?? '' }}" class="sale-presentation-id">
                                                    <input type="text" value="{{ $item['search'] ?? $selectedPresentation['label'] ?? '' }}" placeholder="Busca por nombre, código o barcode" class="sale-search block w-full rounded-md border-gray-300 shadow-sm" autocomplete="off">
                                                    <div class="sale-search-results mt-2 hidden rounded-md border border-gray-200 bg-white shadow-sm"></div>
                                                    <p class="selected-presentation mt-2 text-xs text-gray-500">{{ $selectedPresentation['label'] ?? 'Sin presentación seleccionada' }}</p>
                                                    <p class="stock-preview mt-1 text-xs text-gray-400">Disponibilidad estimada: {{ $selectedPresentation['available_sale_units'] ?? '0.000' }}</p>
                                                    <div class="sale-line-signals mt-2 flex flex-wrap gap-2"></div>
                                                    @error("items.$index.sale_presentation_id")
                                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </td>
                                                <td class="px-3 py-2 align-top">
                                                    <input name="items[{{ $index }}][quantity]" type="number" step="0.001" min="0.001" value="{{ $item['quantity'] ?? 1 }}" class="sale-quantity-input block w-32 rounded-md border-gray-300 shadow-sm" required>
                                                </td>
                                                <td class="px-3 py-2 align-top"><span class="price-preview text-gray-700">—</span></td>
                                                <td class="px-3 py-2 align-top">
                                                    <input name="items[{{ $index }}][manual_unit_price]" type="number" step="0.01" min="0.01" value="{{ $item['manual_unit_price'] ?? '' }}" placeholder="Opcional" class="manual-price-input block w-32 rounded-md border-gray-300 shadow-sm">
                                                </td>
                                                <td class="px-3 py-2 align-top">
                                                    <input name="items[{{ $index }}][manual_price_reason]" type="text" value="{{ $item['manual_price_reason'] ?? '' }}" placeholder="Obligatorio si cambia" class="manual-price-reason-input block w-48 rounded-md border-gray-300 shadow-sm">
                                                    <p class="manual-price-feedback mt-1 text-xs text-gray-400">Solo justifica si cambias el precio vigente.</p>
                                                </td>
                                                <td class="px-3 py-2 text-right align-top"><button type="button" class="remove-line text-red-600">Quitar</button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Pago en efectivo</label>
                                <input name="payments[cash]" type="number" step="0.01" min="0" value="{{ old('payments.cash', 0) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Pago por transferencia</label>
                                <input name="payments[transfer]" type="number" step="0.01" min="0" value="{{ old('payments.transfer', 0) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                        </div>

                        <div class="grid gap-4 rounded-lg border border-amber-200 bg-amber-50 p-4 md:grid-cols-2 text-sm">
                            <label class="flex items-start gap-3">
                                <input type="checkbox" name="confirm_stock_warnings" value="1" @checked(old('confirm_stock_warnings')) class="mt-1 rounded border-gray-300 text-amber-600 shadow-sm">
                                <span>
                                    <span class="block font-medium text-amber-900">Confirmar venta con stock insuficiente</span>
                                    <span class="mt-1 block text-amber-800">Marca esta opción si aceptas continuar aunque alguna línea requiera más stock del disponible.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3">
                                <input type="checkbox" name="confirm_cost_warnings" value="1" @checked(old('confirm_cost_warnings')) class="mt-1 rounded border-gray-300 text-amber-600 shadow-sm">
                                <span>
                                    <span class="block font-medium text-amber-900">Confirmar venta con costo pendiente</span>
                                    <span class="mt-1 block text-amber-800">Marca esta opción si aceptas continuar aunque el sistema no tenga costo suficiente para alguna línea.</span>
                                </span>
                            </label>
                        </div>

                        @if ($errors->any())
                            <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                                Revisa los datos ingresados. Hay errores en la venta.
                                @if ($errors->has('payments'))
                                    <div class="mt-1">{{ $errors->first('payments') }}</div>
                                @endif
                                @if ($errors->has('customer_id'))
                                    <div class="mt-1">{{ $errors->first('customer_id') }}</div>
                                @endif
                                @if ($errors->has('warnings'))
                                    <div class="mt-1">{{ $errors->first('warnings') }}</div>
                                @endif
                                @if ($errors->has('items'))
                                    <div class="mt-1">{{ $errors->first('items') }}</div>
                                @endif
                            </div>
                        @endif

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('sales.index') }}" class="text-sm text-gray-600">Cancelar</a>
                            <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Guardar venta</button>
                        </div>
                    </div>

                    <aside class="order-1 xl:order-2">
                        <div class="space-y-4 xl:sticky xl:top-6">
                            <section class="rounded-lg border border-gray-200 bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado operativo de la venta</p>
                                        <p id="sale-status-title" class="mt-2 text-base font-semibold text-slate-900">Completa las líneas para preparar la venta.</p>
                                        <p id="sale-status-description" class="mt-1 text-sm text-slate-600">El resumen te dirá si falta cliente, caja o confirmación de warnings antes de guardar.</p>
                                    </div>
                                    <span id="sale-status-badge" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">En preparación</span>
                                </div>

                                <div class="mt-4 rounded-lg border border-slate-200 bg-white p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Checklist operativo</p>
                                    <div class="mt-3 space-y-2 text-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-slate-600">Caja para pagos</span>
                                            <span id="sale-check-cash" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">Sin pagos registrados</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-slate-600">Cliente para saldo</span>
                                            <span id="sale-check-customer" class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">Sin saldo pendiente</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-slate-600">Warnings críticos</span>
                                            <span id="sale-check-warnings" class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">Sin warnings probables</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-slate-600">Override manual</span>
                                            <span id="sale-check-override" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">Sin override</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2 text-sm">
                                    <a href="{{ route('customers.index') }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 font-medium text-gray-700">Clientes</a>
                                    <a href="{{ route('receivables.index') }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 font-medium text-gray-700">Cobranza</a>
                                    <a href="{{ route('cash.index') }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 font-medium text-gray-700">Caja</a>
                                </div>
                            </section>

                            <section class="rounded-lg border border-gray-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Resumen monetario</p>
                                <div class="mt-3 grid gap-3 text-sm">
                                    <div class="rounded-lg bg-gray-50 p-3">
                                        <p class="text-gray-500">Total estimado</p>
                                        <p id="sale-total-preview" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3">
                                        <p class="text-gray-500">Pagos registrados</p>
                                        <p id="sale-paid-preview" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3">
                                        <p id="sale-credit-label" class="text-gray-500">Saldo pendiente estimado</p>
                                        <p id="sale-credit-preview" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-lg border border-gray-200 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Métodos activos</p>
                                    <div id="sale-payment-methods" class="mt-2 flex flex-wrap gap-2">
                                        <x-signal-badge tone="neutral">Sin pagos registrados</x-signal-badge>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-lg border border-gray-200 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Composición de cierre</p>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                                        <div class="rounded-lg bg-gray-50 p-3">
                                            <p class="text-gray-500">Cobro inmediato</p>
                                            <p id="sale-paid-composition" class="mt-1 text-base font-semibold text-gray-900">$0.00</p>
                                        </div>
                                        <div class="rounded-lg bg-gray-50 p-3">
                                            <p class="text-gray-500">Queda como fiado</p>
                                            <p id="sale-credit-composition" class="mt-1 text-base font-semibold text-gray-900">$0.00</p>
                                        </div>
                                        <div class="rounded-lg bg-gray-50 p-3">
                                            <p class="text-gray-500">Lectura actual</p>
                                            <p id="sale-composition-caption" class="mt-1 text-sm font-medium text-gray-700">Sin líneas seleccionadas.</p>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-lg border border-gray-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Señales a revisar</p>
                                <div id="sale-warning-signals" class="mt-3 flex flex-wrap gap-2"></div>
                                <p id="sale-warning-empty" class="mt-3 text-sm text-gray-500">Sin señales críticas por ahora.</p>
                            </section>

                            <section class="rounded-lg border border-gray-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Líneas listas / pendientes</p>
                                <div class="mt-3 grid gap-3 sm:grid-cols-3 xl:grid-cols-1 text-sm">
                                    <div class="rounded-lg bg-gray-50 p-3">
                                        <p class="text-gray-500">Listas</p>
                                        <p id="sale-lines-ready-count" class="mt-1 text-base font-semibold text-gray-900">0</p>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3">
                                        <p class="text-gray-500">Con warning</p>
                                        <p id="sale-lines-warning-count" class="mt-1 text-base font-semibold text-gray-900">0</p>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3">
                                        <p class="text-gray-500">Con bloqueo</p>
                                        <p id="sale-lines-blocked-count" class="mt-1 text-base font-semibold text-gray-900">0</p>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-lg border border-gray-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Siguiente paso sugerido</p>
                                <p id="sale-next-step-title" class="mt-2 text-sm font-semibold text-gray-900">Empieza agregando una presentación.</p>
                                <p id="sale-next-step-description" class="mt-1 text-sm text-gray-600">El panel irá cambiando según cliente, pagos, warnings y override.</p>

                                <div id="sale-context-actions" class="mt-4 flex flex-wrap gap-2">
                                    <a href="{{ route('customers.index') }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700">Ir a clientes</a>
                                    <a href="{{ route('cash.index') }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700">Ir a caja</a>
                                </div>
                            </section>
                        </div>
                    </aside>
                </div>
            </form>
        </div>
    </div>

    <script type="application/json" id="presentation-options">@json($presentationOptions)</script>
    <script type="application/json" id="sale-runtime">@json(['hasOpenCashSession' => (bool) $currentCashSession])</script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const options = JSON.parse(document.getElementById('presentation-options').textContent);
            const runtime = JSON.parse(document.getElementById('sale-runtime').textContent);
            const searchUrl = "{{ route('sales.search') }}";
            const customersUrl = "{{ route('customers.index') }}";
            const cashUrl = "{{ route('cash.index') }}";
            const receivablesUrl = "{{ route('receivables.index') }}";
            const reportsUrl = "{{ route('reports.index') }}";
            const salesIndexUrl = "{{ route('sales.index') }}";
            const body = document.querySelector('#sale-items-table tbody');
            const addButton = document.getElementById('add-sale-item');
            const cashInput = document.querySelector('input[name="payments[cash]"]');
            const transferInput = document.querySelector('input[name="payments[transfer]"]');
            const customerSelect = document.getElementById('sale-customer-id');
            const customerFeedback = document.getElementById('sale-customer-feedback');
            const confirmStockWarningsInput = document.querySelector('input[name="confirm_stock_warnings"]');
            const confirmCostWarningsInput = document.querySelector('input[name="confirm_cost_warnings"]');
            const totalPreview = document.getElementById('sale-total-preview');
            const paidPreview = document.getElementById('sale-paid-preview');
            const creditPreview = document.getElementById('sale-credit-preview');
            const creditLabel = document.getElementById('sale-credit-label');
            const paymentMethods = document.getElementById('sale-payment-methods');
            const paidComposition = document.getElementById('sale-paid-composition');
            const creditComposition = document.getElementById('sale-credit-composition');
            const compositionCaption = document.getElementById('sale-composition-caption');
            const statusBadge = document.getElementById('sale-status-badge');
            const statusTitle = document.getElementById('sale-status-title');
            const statusDescription = document.getElementById('sale-status-description');
            const warningSignals = document.getElementById('sale-warning-signals');
            const warningEmpty = document.getElementById('sale-warning-empty');
            const checklistCash = document.getElementById('sale-check-cash');
            const checklistCustomer = document.getElementById('sale-check-customer');
            const checklistWarnings = document.getElementById('sale-check-warnings');
            const checklistOverride = document.getElementById('sale-check-override');
            const readyLinesCount = document.getElementById('sale-lines-ready-count');
            const warningLinesCount = document.getElementById('sale-lines-warning-count');
            const blockedLinesCount = document.getElementById('sale-lines-blocked-count');
            const nextStepTitle = document.getElementById('sale-next-step-title');
            const nextStepDescription = document.getElementById('sale-next-step-description');
            const contextActions = document.getElementById('sale-context-actions');
            const hasOpenCashSession = Boolean(runtime.hasOpenCashSession);
            let index = body.querySelectorAll('tr').length;
            const debounceTimers = new WeakMap();

            const toneClasses = {
                success: 'bg-emerald-100 text-emerald-700',
                warning: 'bg-amber-100 text-amber-800',
                danger: 'bg-red-100 text-red-700',
                info: 'bg-indigo-100 text-indigo-700',
                neutral: 'bg-slate-100 text-slate-700',
                cost: 'bg-orange-100 text-orange-800',
            };

            function formatMoney(value) {
                return `$${Number(value).toFixed(2)}`;
            }

            function findOptionById(id) {
                return options.find((option) => String(option.id) === String(id));
            }

            function setTone(element, tone) {
                element.className = `rounded-full px-2.5 py-1 text-xs font-medium ${toneClasses[tone] ?? toneClasses.neutral}`;
            }

            function renderSignal(text, tone = 'neutral') {
                return `<span class="rounded-full px-2 py-1 text-xs font-medium ${toneClasses[tone] ?? toneClasses.neutral}">${text}</span>`;
            }

            function renderActionButton(href, label, tone = 'neutral') {
                const classes = tone === 'primary'
                    ? 'rounded-md border border-indigo-300 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700'
                    : 'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700';

                return `<a href="${href}" class="${classes}">${label}</a>`;
            }

            function setSelectedPresentation(row, option) {
                row.querySelector('.sale-presentation-id').value = option?.id ?? '';
                row.querySelector('.selected-presentation').textContent = option?.label ?? 'Sin presentación seleccionada';
                row.querySelector('.price-preview').textContent = option?.price ? `$${option.price}${option.min_price ? ` · mínimo $${option.min_price}` : ''}` : '—';
                row.querySelector('.stock-preview').textContent = `Disponibilidad estimada: ${option?.available_sale_units ?? '0.000'}`;
                if (option) {
                    row.querySelector('.sale-search').value = option.label;
                }
                hideResults(row);
                refreshSummary();
            }

            function renderResults(row, results) {
                const container = row.querySelector('.sale-search-results');

                if (!results.length) {
                    container.innerHTML = '<div class="px-3 py-2 text-xs text-gray-500">Sin resultados</div>';
                    container.classList.remove('hidden');
                    return;
                }

                container.innerHTML = results.map((result) => `
                    <button type="button" class="sale-result-item flex w-full items-start justify-between gap-3 px-3 py-2 text-left hover:bg-gray-50" data-id="${result.id}">
                        <span>
                            <span class="block text-sm font-medium text-gray-800">${result.label}</span>
                            <span class="mt-1 block text-xs text-gray-500">Código: ${result.internal_code ?? '—'} · Barcode: ${result.barcode ?? '—'} · Stock aprox.: ${result.available_sale_units}</span>
                        </span>
                        <span class="text-sm font-medium text-gray-700">${result.price ? `$${result.price}` : '—'}</span>
                    </button>
                `).join('');

                container.classList.remove('hidden');

                container.querySelectorAll('.sale-result-item').forEach((button) => {
                    button.addEventListener('click', () => {
                        const selected = results.find((result) => String(result.id) === button.dataset.id);
                        setSelectedPresentation(row, selected);
                    });
                });
            }

            function hideResults(row) {
                const container = row.querySelector('.sale-search-results');
                container.classList.add('hidden');
                container.innerHTML = '';
            }

            async function searchPresentations(row, query) {
                const response = await fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    hideResults(row);
                    return;
                }

                const data = await response.json();
                const results = data.results ?? [];
                const exactMatch = results.find((result) => result.exact_code_match);

                if (data.auto_select && exactMatch) {
                    setSelectedPresentation(row, exactMatch);
                    return;
                }

                renderResults(row, results);
            }

            function analyzeRow(row) {
                const presentationId = row.querySelector('.sale-presentation-id').value;
                const quantity = Number(row.querySelector('.sale-quantity-input')?.value || 0);
                const selected = findOptionById(presentationId);
                const manualPriceInput = row.querySelector('.manual-price-input');
                const reasonInput = row.querySelector('.manual-price-reason-input');
                const feedback = row.querySelector('.manual-price-feedback');
                const signalsContainer = row.querySelector('.sale-line-signals');
                const manualPrice = manualPriceInput && manualPriceInput.value !== '' ? Number(manualPriceInput.value) : null;
                const selectedPrice = selected && selected.price ? Number(selected.price) : null;
                const availableSaleUnits = selected ? Number(selected.available_sale_units || 0) : 0;
                const signals = [];
                const flags = {
                    hasSelection: Boolean(selected),
                    probableStockWarning: false,
                    probableCostWarning: false,
                    hasOverride: false,
                    missingOverrideReason: false,
                    status: 'empty',
                };

                if (selected && manualPrice !== null && selectedPrice !== null && manualPrice !== selectedPrice) {
                    flags.hasOverride = true;
                    signals.push(renderSignal('Override manual activo', 'info'));

                    if (!reasonInput.value.trim()) {
                        flags.missingOverrideReason = true;
                        signals.push(renderSignal('Falta motivo de override', 'danger'));
                        feedback.textContent = 'Debes justificar el override antes de guardar.';
                        feedback.className = 'manual-price-feedback mt-1 text-xs text-red-600';
                    } else {
                        feedback.textContent = 'Override justificado para esta línea.';
                        feedback.className = 'manual-price-feedback mt-1 text-xs text-indigo-600';
                    }
                } else {
                    feedback.textContent = 'Solo justifica si cambias el precio vigente.';
                    feedback.className = 'manual-price-feedback mt-1 text-xs text-gray-400';
                }

                if (selected && quantity > availableSaleUnits) {
                    flags.probableStockWarning = true;
                    flags.probableCostWarning = true;
                    signals.push(renderSignal('Stock insuficiente probable', 'warning'));
                    signals.push(renderSignal('Costo pendiente probable', 'cost'));
                } else if (selected && availableSaleUnits <= 0 && quantity > 0) {
                    flags.probableCostWarning = true;
                    signals.push(renderSignal('Costo pendiente probable', 'cost'));
                }

                if (!selected) {
                    flags.status = 'empty';
                } else if (flags.missingOverrideReason) {
                    flags.status = 'blocked';
                } else if (flags.probableStockWarning || flags.probableCostWarning || flags.hasOverride) {
                    flags.status = 'warning';
                } else {
                    flags.status = 'ready';
                }

                if (flags.status === 'ready') {
                    signals.push(renderSignal('Línea lista', 'success'));
                } else if (flags.status === 'blocked') {
                    signals.push(renderSignal('Línea bloqueada', 'danger'));
                }

                signalsContainer.innerHTML = signals.join('');

                return flags;
            }

            function refreshSummary() {
                let total = 0;
                let selectedItems = 0;
                let hasProbableStockWarning = false;
                let hasProbableCostWarning = false;
                let hasOverride = false;
                let hasMissingOverrideReason = false;
                let readyLines = 0;
                let warningLines = 0;
                let blockedLines = 0;

                body.querySelectorAll('tr').forEach((row) => {
                    const presentationId = row.querySelector('.sale-presentation-id').value;
                    const selected = findOptionById(presentationId);
                    const quantityInput = row.querySelector('.sale-quantity-input');
                    const manualPriceInput = row.querySelector('.manual-price-input');
                    const manualPrice = manualPriceInput && manualPriceInput.value !== '' ? Number(manualPriceInput.value) : null;
                    const price = manualPrice ?? (selected && selected.price ? Number(selected.price) : 0);
                    const quantity = quantityInput ? Number(quantityInput.value || 0) : 0;
                    const flags = analyzeRow(row);

                    if (flags.hasSelection) {
                        selectedItems += 1;
                    }

                    hasProbableStockWarning = hasProbableStockWarning || flags.probableStockWarning;
                    hasProbableCostWarning = hasProbableCostWarning || flags.probableCostWarning;
                    hasOverride = hasOverride || flags.hasOverride;
                    hasMissingOverrideReason = hasMissingOverrideReason || flags.missingOverrideReason;

                    if (flags.status === 'ready') {
                        readyLines += 1;
                    } else if (flags.status === 'warning') {
                        warningLines += 1;
                    } else if (flags.status === 'blocked') {
                        blockedLines += 1;
                    }

                    total += price * quantity;
                });

                const paid = Number(cashInput.value || 0) + Number(transferInput.value || 0);
                const credit = Math.max(total - paid, 0);
                const customerSelected = Boolean(customerSelect.value);
                const needsCustomer = credit > 0;
                const needsCashSession = paid > 0;
                const warningsPending = (hasProbableStockWarning && !confirmStockWarningsInput.checked)
                    || (hasProbableCostWarning && !confirmCostWarningsInput.checked);

                totalPreview.textContent = formatMoney(total);
                paidPreview.textContent = formatMoney(paid);
                creditPreview.textContent = formatMoney(credit);
                paidComposition.textContent = formatMoney(Math.min(paid, total));
                creditComposition.textContent = formatMoney(credit);
                creditLabel.textContent = credit > 0 && customerSelected ? 'Monto a fiar' : 'Saldo pendiente estimado';
                readyLinesCount.textContent = String(readyLines);
                warningLinesCount.textContent = String(warningLines);
                blockedLinesCount.textContent = String(blockedLines);

                if (selectedItems === 0) {
                    compositionCaption.textContent = 'Sin líneas seleccionadas.';
                } else if (credit > 0 && customerSelected) {
                    compositionCaption.textContent = 'La venta mezcla cobro inmediato con saldo trazado al cliente.';
                } else if (credit > 0) {
                    compositionCaption.textContent = 'Hay saldo pendiente, pero todavía no está asociado a un cliente.';
                } else if (paid > 0) {
                    compositionCaption.textContent = 'La venta quedaría cobrada al momento de registrarla.';
                } else {
                    compositionCaption.textContent = 'La venta se está preparando sin pagos todavía.';
                }

                if (cashInput.value && Number(cashInput.value) > 0 && Number(transferInput.value || 0) > 0) {
                    paymentMethods.innerHTML = `${renderSignal('Efectivo', 'success')}${renderSignal('Transferencia', 'info')}`;
                } else if (Number(cashInput.value || 0) > 0) {
                    paymentMethods.innerHTML = renderSignal('Efectivo', 'success');
                } else if (Number(transferInput.value || 0) > 0) {
                    paymentMethods.innerHTML = renderSignal('Transferencia', 'info');
                } else {
                    paymentMethods.innerHTML = renderSignal('Sin pagos registrados', 'neutral');
                }

                if (!needsCashSession) {
                    setTone(checklistCash, 'neutral');
                    checklistCash.textContent = 'Sin pagos registrados';
                } else if (hasOpenCashSession) {
                    setTone(checklistCash, 'success');
                    checklistCash.textContent = 'Caja lista para cobrar';
                } else {
                    setTone(checklistCash, 'danger');
                    checklistCash.textContent = 'Abrir caja antes de cobrar';
                }

                if (!needsCustomer) {
                    setTone(checklistCustomer, 'success');
                    checklistCustomer.textContent = 'Sin saldo pendiente';
                } else if (customerSelected) {
                    setTone(checklistCustomer, 'success');
                    checklistCustomer.textContent = 'Cliente listo para fiado';
                } else {
                    setTone(checklistCustomer, 'danger');
                    checklistCustomer.textContent = 'Selecciona cliente';
                }

                if (!hasProbableStockWarning && !hasProbableCostWarning) {
                    setTone(checklistWarnings, 'success');
                    checklistWarnings.textContent = 'Sin warnings probables';
                } else if (warningsPending) {
                    setTone(checklistWarnings, 'danger');
                    checklistWarnings.textContent = 'Confirma warnings';
                } else {
                    setTone(checklistWarnings, 'warning');
                    checklistWarnings.textContent = 'Warnings confirmados';
                }

                if (!hasOverride) {
                    setTone(checklistOverride, 'neutral');
                    checklistOverride.textContent = 'Sin override';
                } else if (hasMissingOverrideReason) {
                    setTone(checklistOverride, 'danger');
                    checklistOverride.textContent = 'Falta justificar';
                } else {
                    setTone(checklistOverride, 'info');
                    checklistOverride.textContent = 'Override justificado';
                }

                const warningSignalsList = [];

                if (hasProbableStockWarning) {
                    warningSignalsList.push(renderSignal('Stock insuficiente probable', 'warning'));
                }

                if (hasProbableCostWarning) {
                    warningSignalsList.push(renderSignal('Costo pendiente probable', 'cost'));
                }

                if (hasOverride) {
                    warningSignalsList.push(renderSignal('Override manual activo', 'info'));
                }

                if (needsCustomer && !customerSelected) {
                    warningSignalsList.push(renderSignal('Saldo pendiente sin cliente', 'danger'));
                }

                if (needsCashSession && !hasOpenCashSession) {
                    warningSignalsList.push(renderSignal('Pago sin caja abierta', 'danger'));
                }

                warningSignals.innerHTML = warningSignalsList.join('');
                warningEmpty.classList.toggle('hidden', warningSignalsList.length > 0);

                if (needsCustomer && !customerSelected) {
                    customerSelect.classList.add('border-red-300', 'bg-red-50', 'text-red-700');
                    customerFeedback.textContent = 'Hay saldo pendiente: selecciona cliente antes de guardar esta venta.';
                    customerFeedback.className = 'mt-1 text-xs text-red-600';
                } else {
                    customerSelect.classList.remove('border-red-300', 'bg-red-50', 'text-red-700');
                    customerFeedback.textContent = 'Selecciona cliente solo si habrá saldo a fiar o si quieres trazabilidad nominal.';
                    customerFeedback.className = 'mt-1 text-xs text-gray-500';
                }

                if (selectedItems === 0) {
                    setTone(statusBadge, 'neutral');
                    statusBadge.textContent = 'En preparación';
                    statusTitle.textContent = 'Completa las líneas para preparar la venta.';
                    statusDescription.textContent = 'Empieza seleccionando al menos una presentación para que el resumen operativo cobre sentido.';
                    nextStepTitle.textContent = 'Empieza agregando una presentación.';
                    nextStepDescription.textContent = 'Sin líneas no se puede calcular pago, fiado ni señales reales.';
                    contextActions.innerHTML = [
                        renderActionButton(customersUrl, 'Ir a clientes'),
                        renderActionButton(cashUrl, 'Ir a caja')
                    ].join('');
                } else if (needsCashSession && !hasOpenCashSession) {
                    setTone(statusBadge, 'danger');
                    statusBadge.textContent = 'Bloqueada por caja';
                    statusTitle.textContent = 'No puede registrar pagos sin caja abierta.';
                    statusDescription.textContent = 'Puedes seguir preparando la venta, pero debes abrir caja antes de cobrar efectivo o transferencia.';
                    nextStepTitle.textContent = 'Abre caja antes de cobrar.';
                    nextStepDescription.textContent = 'Ya hay pagos cargados; la siguiente acción correcta es habilitar una caja abierta.';
                    contextActions.innerHTML = renderActionButton(cashUrl, 'Revisar caja', 'primary');
                } else if (needsCustomer && !customerSelected) {
                    setTone(statusBadge, 'danger');
                    statusBadge.textContent = 'Requiere cliente';
                    statusTitle.textContent = 'Requiere cliente para dejar saldo pendiente.';
                    statusDescription.textContent = 'El resumen ya detectó monto a fiar; selecciona un cliente antes de guardar la venta.';
                    nextStepTitle.textContent = 'Selecciona un cliente para soportar el fiado.';
                    nextStepDescription.textContent = 'Sin cliente no hay trazabilidad correcta del saldo pendiente.';
                    contextActions.innerHTML = [
                        renderActionButton(customersUrl, 'Revisar clientes', 'primary'),
                        renderActionButton(receivablesUrl, 'Ver cobranza')
                    ].join('');
                } else if (hasMissingOverrideReason) {
                    setTone(statusBadge, 'danger');
                    statusBadge.textContent = 'Falta justificar';
                    statusTitle.textContent = 'Hay override manual sin motivo.';
                    statusDescription.textContent = 'Completa el motivo del cambio manual de precio para mantener trazabilidad operativa.';
                    nextStepTitle.textContent = 'Justifica el override antes de continuar.';
                    nextStepDescription.textContent = 'El cambio manual de precio ya está detectado, pero todavía no tiene motivo suficiente.';
                    contextActions.innerHTML = renderActionButton(reportsUrl, 'Ver reportes');
                } else if (warningsPending) {
                    setTone(statusBadge, 'warning');
                    statusBadge.textContent = 'Requiere confirmación';
                    statusTitle.textContent = 'Debes confirmar los warnings antes de guardar.';
                    statusDescription.textContent = 'Hay señales probables de stock insuficiente o costo pendiente; revisa y confirma conscientemente.';
                    nextStepTitle.textContent = 'Revisa las señales y confirma conscientemente.';
                    nextStepDescription.textContent = 'No hace falta cambiar de módulo todavía; primero decide si aceptas vender con esos warnings.';
                    contextActions.innerHTML = [
                        renderActionButton(reportsUrl, 'Ver reportes'),
                        renderActionButton(cashUrl, 'Ver caja')
                    ].join('');
                } else {
                    setTone(statusBadge, 'success');
                    statusBadge.textContent = 'Lista para guardar';
                    statusTitle.textContent = 'La venta está lista para registrarse.';
                    statusDescription.textContent = 'El resumen no detecta bloqueos operativos visibles para esta captura.';
                    if (credit > 0 && customerSelected) {
                        nextStepTitle.textContent = 'Puedes guardar: la parte fiada quedó respaldada.';
                        nextStepDescription.textContent = 'El cliente ya está seleccionado y el saldo pendiente quedará trazado para cobranza.';
                        contextActions.innerHTML = [
                            renderActionButton(receivablesUrl, 'Ir a cobranza', 'primary'),
                            renderActionButton(customersUrl, 'Ver clientes')
                        ].join('');
                    } else if (paid > 0) {
                        nextStepTitle.textContent = 'Puedes guardar: el cobro inmediato está consistente.';
                        nextStepDescription.textContent = 'La venta ya tiene pago cargado y no muestra bloqueos operativos visibles.';
                        contextActions.innerHTML = [
                            renderActionButton(cashUrl, 'Ver caja', 'primary'),
                            renderActionButton(salesIndexUrl, 'Volver a ventas')
                        ].join('');
                    } else {
                        nextStepTitle.textContent = 'Puedes guardar o seguir completando pagos.';
                        nextStepDescription.textContent = 'No hay bloqueos visibles; decide si la venta quedará totalmente pendiente o si aún registrarás cobro.';
                        contextActions.innerHTML = [
                            renderActionButton(cashUrl, 'Ver caja'),
                            renderActionButton(receivablesUrl, 'Ver cobranza')
                        ].join('');
                    }
                }
            }

            function refreshPrices() {
                body.querySelectorAll('tr').forEach((row) => {
                    const presentationId = row.querySelector('.sale-presentation-id').value;
                    const preview = row.querySelector('.price-preview');
                    const selected = findOptionById(presentationId);
                    preview.textContent = selected && selected.price ? `$${selected.price}${selected.min_price ? ` · mínimo $${selected.min_price}` : ''}` : '—';
                    row.querySelector('.selected-presentation').textContent = selected?.label ?? 'Sin presentación seleccionada';
                    row.querySelector('.stock-preview').textContent = `Disponibilidad estimada: ${selected?.available_sale_units ?? '0.000'}`;
                });

                refreshSummary();
            }

            function bindRow(row) {
                row.querySelector('.remove-line').onclick = () => {
                    hideResults(row);
                    if (body.querySelectorAll('tr').length > 1) {
                        row.remove();
                    }
                    refreshSummary();
                };

                const searchInput = row.querySelector('.sale-search');
                searchInput.addEventListener('input', () => {
                    const query = searchInput.value.trim();
                    row.querySelector('.sale-presentation-id').value = '';
                    row.querySelector('.selected-presentation').textContent = 'Sin presentación seleccionada';
                    row.querySelector('.price-preview').textContent = '—';
                    row.querySelector('.stock-preview').textContent = 'Disponibilidad estimada: 0.000';
                    row.querySelector('.sale-line-signals').innerHTML = '';
                    refreshSummary();

                    clearTimeout(debounceTimers.get(searchInput));

                    if (query.length < 2) {
                        hideResults(row);
                        return;
                    }

                    const timer = setTimeout(() => {
                        searchPresentations(row, query);
                    }, 250);

                    debounceTimers.set(searchInput, timer);
                });

                searchInput.addEventListener('focus', () => {
                    const query = searchInput.value.trim();
                    if (query.length >= 2) {
                        searchPresentations(row, query);
                    }
                });

                document.addEventListener('click', (event) => {
                    if (!row.contains(event.target)) {
                        hideResults(row);
                    }
                });

                row.querySelectorAll('input').forEach((input) => {
                    if (input.classList.contains('sale-search')) {
                        return;
                    }

                    input.addEventListener('input', refreshSummary);
                    input.addEventListener('change', refreshSummary);
                });
            }

            function bindActions() {
                body.querySelectorAll('tr').forEach((row) => {
                    if (row.dataset.bound === '1') {
                        return;
                    }

                    bindRow(row);
                    row.dataset.bound = '1';
                });
            }

            addButton.addEventListener('click', () => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="px-3 py-2 align-top">
                        <input type="hidden" name="items[${index}][sale_presentation_id]" value="" class="sale-presentation-id">
                        <input type="text" placeholder="Busca por nombre, código o barcode" class="sale-search block w-full rounded-md border-gray-300 shadow-sm" autocomplete="off">
                        <div class="sale-search-results mt-2 hidden rounded-md border border-gray-200 bg-white shadow-sm"></div>
                        <p class="selected-presentation mt-2 text-xs text-gray-500">Sin presentación seleccionada</p>
                        <p class="stock-preview mt-1 text-xs text-gray-400">Disponibilidad estimada: 0.000</p>
                        <div class="sale-line-signals mt-2 flex flex-wrap gap-2"></div>
                    </td>
                    <td class="px-3 py-2 align-top"><input name="items[${index}][quantity]" type="number" step="0.001" min="0.001" value="1" class="sale-quantity-input block w-32 rounded-md border-gray-300 shadow-sm" required></td>
                    <td class="px-3 py-2 align-top"><span class="price-preview text-gray-700">—</span></td>
                    <td class="px-3 py-2 align-top"><input name="items[${index}][manual_unit_price]" type="number" step="0.01" min="0.01" placeholder="Opcional" class="manual-price-input block w-32 rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2 align-top">
                        <input name="items[${index}][manual_price_reason]" type="text" placeholder="Obligatorio si cambia" class="manual-price-reason-input block w-48 rounded-md border-gray-300 shadow-sm">
                        <p class="manual-price-feedback mt-1 text-xs text-gray-400">Solo justifica si cambias el precio vigente.</p>
                    </td>
                    <td class="px-3 py-2 text-right align-top"><button type="button" class="remove-line text-red-600">Quitar</button></td>
                `;

                body.appendChild(row);
                index += 1;
                bindActions();
                refreshPrices();
            });

            body.querySelectorAll('tr').forEach((row) => {
                const selectedId = row.querySelector('.sale-presentation-id').value;
                if (selectedId) {
                    const selected = findOptionById(selectedId);
                    setSelectedPresentation(row, selected);
                }
            });

            bindActions();
            refreshPrices();
            cashInput.addEventListener('input', refreshSummary);
            transferInput.addEventListener('input', refreshSummary);
            customerSelect.addEventListener('change', refreshSummary);
            confirmStockWarningsInput.addEventListener('change', refreshSummary);
            confirmCostWarningsInput.addEventListener('change', refreshSummary);
        });
    </script>
</x-app-layout>
