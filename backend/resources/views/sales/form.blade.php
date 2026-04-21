@php
    $presentationOptions = $presentations->map(function ($presentation) {
        $price = $presentation->prices->firstWhere('ends_at', null) ?? $presentation->prices->first();

        return [
            'id' => $presentation->id,
            'label' => $presentation->variant->product->name . ' — ' . $presentation->variant->name . ' — ' . $presentation->name,
            'price' => $price ? number_format($price->price_amount / 100, 2, '.', '') : null,
            'min_price' => $price && $price->min_price_amount !== null ? number_format($price->min_price_amount / 100, 2, '.', '') : null,
            'barcode' => $presentation->variant->barcode,
            'internal_code' => $presentation->variant->product->internal_code,
        ];
    })->values();

    $presentationMap = $presentationOptions->keyBy('id');
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Nueva venta</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (! $currentCashSession)
                <div class="mb-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    No hay una caja abierta. Puedes preparar la venta, pero no podrás registrar pagos hasta abrir caja.
                </div>
            @endif

            <form method="POST" action="{{ route('sales.store') }}" class="space-y-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @csrf

                <div class="grid gap-6 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha</label>
                        <input name="sold_at" type="datetime-local" value="{{ old('sold_at', now()->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cliente</label>
                        <select name="customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Venta anónima</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notas</label>
                        <input name="notes" type="text" value="{{ old('notes') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between bg-gray-50 px-4 py-3">
                        <h3 class="font-medium text-gray-800">Líneas de venta</h3>
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
                                @php($oldItems = old('items', [['sale_presentation_id' => '', 'quantity' => 1, 'search' => '', 'manual_unit_price' => '', 'manual_price_reason' => '']]))
                                @foreach ($oldItems as $index => $item)
                                    @php($selectedPresentation = filled($item['sale_presentation_id'] ?? null) ? $presentationMap->get((int) $item['sale_presentation_id']) : null)
                                    <tr>
                                        <td class="px-3 py-2">
                                            <input type="hidden" name="items[{{ $index }}][sale_presentation_id]" value="{{ $item['sale_presentation_id'] ?? '' }}" class="sale-presentation-id">
                                            <input type="text" value="{{ $item['search'] ?? $selectedPresentation['label'] ?? '' }}" placeholder="Busca por nombre, código o barcode" class="sale-search block w-full rounded-md border-gray-300 shadow-sm" autocomplete="off">
                                            <div class="sale-search-results mt-2 hidden rounded-md border border-gray-200 bg-white shadow-sm"></div>
                                            <p class="selected-presentation mt-2 text-xs text-gray-500">{{ $selectedPresentation['label'] ?? 'Sin presentación seleccionada' }}</p>
                                            <p class="stock-preview mt-1 text-xs text-gray-400">Disponibilidad estimada: {{ $selectedPresentation['available_sale_units'] ?? '0.000' }}</p>
                                            @error("items.$index.sale_presentation_id")
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-3 py-2"><input name="items[{{ $index }}][quantity]" type="number" step="0.001" min="0.001" value="{{ $item['quantity'] ?? 1 }}" class="block w-32 rounded-md border-gray-300 shadow-sm" required></td>
                                        <td class="px-3 py-2"><span class="price-preview text-gray-700">—</span></td>
                                        <td class="px-3 py-2"><input name="items[{{ $index }}][manual_unit_price]" type="number" step="0.01" min="0.01" value="{{ $item['manual_unit_price'] ?? '' }}" placeholder="Opcional" class="manual-price-input block w-32 rounded-md border-gray-300 shadow-sm"></td>
                                        <td class="px-3 py-2"><input name="items[{{ $index }}][manual_price_reason]" type="text" value="{{ $item['manual_price_reason'] ?? '' }}" placeholder="Obligatorio si cambia" class="block w-48 rounded-md border-gray-300 shadow-sm"></td>
                                        <td class="px-3 py-2 text-right"><button type="button" class="remove-line text-red-600">Quitar</button></td>
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

                <div class="grid gap-4 rounded-lg bg-gray-50 p-4 md:grid-cols-3 text-sm">
                    <div>
                        <p class="text-gray-500">Total estimado</p>
                        <p id="sale-total-preview" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Pagos registrados</p>
                        <p id="sale-paid-preview" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Saldo pendiente estimado</p>
                        <p id="sale-credit-preview" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p>
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
                    </div>
                @endif

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('sales.index') }}" class="text-sm text-gray-600">Cancelar</a>
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Guardar venta</button>
                </div>
            </form>
        </div>
    </div>

    <script type="application/json" id="presentation-options">@json($presentationOptions)</script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const options = JSON.parse(document.getElementById('presentation-options').textContent);
            const searchUrl = "{{ route('sales.search') }}";
            const body = document.querySelector('#sale-items-table tbody');
            const addButton = document.getElementById('add-sale-item');
            const cashInput = document.querySelector('input[name="payments[cash]"]');
            const transferInput = document.querySelector('input[name="payments[transfer]"]');
            const totalPreview = document.getElementById('sale-total-preview');
            const paidPreview = document.getElementById('sale-paid-preview');
            const creditPreview = document.getElementById('sale-credit-preview');
            let index = body.querySelectorAll('tr').length;
            const debounceTimers = new WeakMap();

            function formatMoney(value) {
                return `$${Number(value).toFixed(2)}`;
            }

            function findOptionById(id) {
                return options.find((option) => String(option.id) === String(id));
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

            function refreshSummary() {
                let total = 0;

                body.querySelectorAll('tr').forEach((row) => {
                    const presentationId = row.querySelector('.sale-presentation-id').value;
                    const quantityInput = row.querySelector('input[type="number"]');
                    const selected = findOptionById(presentationId);
                    const manualPriceInput = row.querySelector('.manual-price-input');
                    const manualPrice = manualPriceInput && manualPriceInput.value !== '' ? Number(manualPriceInput.value) : null;
                    const price = manualPrice ?? (selected && selected.price ? Number(selected.price) : 0);
                    const quantity = quantityInput ? Number(quantityInput.value || 0) : 0;
                    total += price * quantity;
                });

                const paid = Number(cashInput.value || 0) + Number(transferInput.value || 0);
                totalPreview.textContent = formatMoney(total);
                paidPreview.textContent = formatMoney(paid);
                creditPreview.textContent = formatMoney(Math.max(total - paid, 0));
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

                row.querySelectorAll('input[type="number"]').forEach((input) => {
                    input.oninput = refreshSummary;
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
                    <td class="px-3 py-2">
                        <input type="hidden" name="items[${index}][sale_presentation_id]" value="" class="sale-presentation-id">
                        <input type="text" placeholder="Busca por nombre, código o barcode" class="sale-search block w-full rounded-md border-gray-300 shadow-sm" autocomplete="off">
                        <div class="sale-search-results mt-2 hidden rounded-md border border-gray-200 bg-white shadow-sm"></div>
                        <p class="selected-presentation mt-2 text-xs text-gray-500">Sin presentación seleccionada</p>
                        <p class="stock-preview mt-1 text-xs text-gray-400">Disponibilidad estimada: 0.000</p>
                    </td>
                    <td class="px-3 py-2"><input name="items[${index}][quantity]" type="number" step="0.001" min="0.001" value="1" class="block w-32 rounded-md border-gray-300 shadow-sm" required></td>
                    <td class="px-3 py-2"><span class="price-preview text-gray-700">—</span></td>
                    <td class="px-3 py-2"><input name="items[${index}][manual_unit_price]" type="number" step="0.01" min="0.01" placeholder="Opcional" class="manual-price-input block w-32 rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2"><input name="items[${index}][manual_price_reason]" type="text" placeholder="Obligatorio si cambia" class="block w-48 rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2 text-right"><button type="button" class="remove-line text-red-600">Quitar</button></td>
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
        });
    </script>
</x-app-layout>
