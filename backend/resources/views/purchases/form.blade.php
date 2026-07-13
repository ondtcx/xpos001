@php
    $variantOptions = $variants->map(function ($variant) {
        return [
            'id' => $variant->id,
            'label' => $variant->product->name . ' — ' . $variant->name,
        ];
    })->values();

    $oldItems = old('items', [['variant_id' => '', 'quantity' => 1, 'unit_cost' => '', 'expiration_date' => '', 'notes' => '']]);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Nueva compra rápida</h2>
                <p class="text-sm text-gray-500">Usa este modo cuando prima la velocidad: una línea por variante, sin prorrateos finos ni bonificaciones complejas.</p>
            </div>
            <a href="{{ route('purchases.detailed.create') }}" class="rounded-md border border-emerald-200 px-4 py-2 text-sm font-medium text-emerald-700">Ir a compra detallada</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Modo actual</p>
                            <h3 class="mt-1 text-base font-semibold text-emerald-900">Compra rápida = velocidad operativa</h3>
                        </div>
                        <x-status-badge tone="success">Rápida</x-status-badge>
                    </div>
                    <ul class="mt-3 space-y-1 text-sm text-emerald-800">
                        <li>- una línea por variante</li>
                        <li>- costo unitario directo</li>
                        <li>- total simple y carga rápida</li>
                    </ul>
                </div>

                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Alternativa</p>
                            <h3 class="mt-1 text-base font-semibold text-emerald-900">Compra detallada = mayor fidelidad</h3>
                        </div>
                        <x-status-badge tone="info">Detallada</x-status-badge>
                    </div>
                    <ul class="mt-3 space-y-1 text-sm text-emerald-800">
                        <li>- impuestos, descuentos y bonificaciones</li>
                        <li>- costos globales y prorrateos</li>
                        <li>- mejor lectura tributaria y auditabilidad</li>
                    </ul>
                </div>
            </div>

            <form method="POST" action="{{ route('purchases.store') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @csrf

                <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <div class="order-2 space-y-6 xl:order-1">
                        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Proveedor</label>
                                <select id="quick-supplier-id" name="supplier_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">Sin proveedor</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Factura</label>
                                <input id="quick-invoice-number" name="invoice_number" type="text" value="{{ old('invoice_number') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha</label>
                                <input name="purchased_at" type="datetime-local" value="{{ old('purchased_at', now()->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tipo de pago</label>
                                <select id="quick-payment-type" name="payment_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="cash" @selected(old('payment_type', 'cash') === 'cash')>Efectivo</option>
                                    <option value="transfer" @selected(old('payment_type') === 'transfer')>Transferencia</option>
                                </select>
                            </div>
                        </div>

                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input id="quick-is-credit" type="checkbox" name="is_credit" value="1" @checked(old('is_credit'))>
                            Compra a crédito
                        </label>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notas</label>
                            <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes') }}</textarea>
                        </div>

                        <div class="overflow-hidden rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between bg-gray-50 px-4 py-3">
                                <div>
                                    <h3 class="font-medium text-gray-800">Líneas de compra</h3>
                                    <p class="mt-1 text-sm text-gray-500">Una línea por variante. Si necesitas bonificaciones separadas o globales finos, cambia a compra detallada.</p>
                                </div>
                                <button type="button" id="add-item" class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white">Agregar línea</button>
                            </div>
                            <div class="border-b border-gray-200 bg-white px-4 py-3">
                                <label class="block text-sm font-medium text-gray-700">Ayuda rápida</label>
                                <p class="mt-1 text-sm text-gray-500">Usa una línea por variante. El total estimado se recalcula automáticamente a medida que cambias cantidad y costo.</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm" id="purchase-items-table">
                                    <thead class="bg-gray-50 text-gray-500">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Variante</th>
                                            <th class="px-3 py-2 text-left">Cantidad</th>
                                            <th class="px-3 py-2 text-left">Costo unitario</th>
                                            <th class="px-3 py-2 text-left">Vencimiento</th>
                                            <th class="px-3 py-2 text-left">Notas</th>
                                            <th class="px-3 py-2 text-left">Estado</th>
                                            <th class="px-3 py-2 text-right">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($oldItems as $index => $item)
                                            <tr>
                                                <td class="px-3 py-2">
                                                    <select name="items[{{ $index }}][variant_id]" class="quick-line-input block w-full rounded-md border-gray-300 shadow-sm" required>
                                                        <option value="">Selecciona una variante</option>
                                                        @foreach ($variants as $variant)
                                                            <option value="{{ $variant->id }}" @selected((string) ($item['variant_id'] ?? '') === (string) $variant->id)>{{ $variant->product->name }} — {{ $variant->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="px-3 py-2"><input name="items[{{ $index }}][quantity]" type="number" step="0.001" min="0.001" value="{{ $item['quantity'] ?? 1 }}" class="quick-line-input block w-32 rounded-md border-gray-300 shadow-sm" required></td>
                                                <td class="px-3 py-2"><input name="items[{{ $index }}][unit_cost]" type="number" step="0.01" min="0" value="{{ $item['unit_cost'] ?? '' }}" class="quick-line-input block w-36 rounded-md border-gray-300 shadow-sm" required></td>
                                                <td class="px-3 py-2"><input name="items[{{ $index }}][expiration_date]" type="date" value="{{ $item['expiration_date'] ?? '' }}" class="block w-40 rounded-md border-gray-300 shadow-sm"></td>
                                                <td class="px-3 py-2"><input name="items[{{ $index }}][notes]" type="text" value="{{ $item['notes'] ?? '' }}" class="block w-full rounded-md border-gray-300 shadow-sm"></td>
                                                <td class="px-3 py-2 text-xs text-gray-600 quick-line-status">Completa variante, cantidad y costo.</td>
                                                <td class="px-3 py-2 text-right"><button type="button" class="remove-line text-red-600">Quitar</button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if ($errors->any())
                            <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                                Revisa los datos ingresados. Hay errores en el formulario.
                            </div>
                        @endif

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('purchases.index') }}" class="text-sm text-gray-600">Cancelar</a>
                            <button class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Guardar compra</button>
                        </div>
                    </div>

                    <aside class="order-1 xl:order-2">
                        <div class="space-y-4 xl:sticky xl:top-6">
                            <section class="rounded-lg border border-gray-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Orientación del modo</p>
                                <p id="quick-mode-title" class="mt-2 text-base font-semibold text-slate-900">Compra rápida alineada con el caso simple.</p>
                                <p id="quick-mode-description" class="mt-1 text-sm text-slate-600">Úsala cuando no necesitas prorrateos, impuestos complejos ni líneas de bonificación separadas.</p>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <a href="{{ route('purchases.detailed.create') }}" class="rounded-md border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">Cambiar a detallada</a>
                                    <a href="{{ route('purchases.index') }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700">Ver compras</a>
                                </div>
                            </section>

                            <section class="rounded-lg border border-gray-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Estado operativo</p>
                                <div class="mt-3 space-y-2 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-gray-600">Proveedor / factura</span>
                                        <span id="quick-check-context" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">Mínimo operativo</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-gray-600">Pago</span>
                                        <span id="quick-check-payment" class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">Contado</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-gray-600">Líneas</span>
                                        <span id="quick-check-lines" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">Pendientes</span>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-lg border border-gray-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Resumen monetario</p>
                                <div class="mt-3 grid gap-3 text-sm">
                                    <div class="rounded-lg bg-gray-50 p-3">
                                        <p class="text-gray-500">Líneas</p>
                                        <p id="purchase-lines-preview" class="mt-1 text-lg font-semibold text-gray-900">0</p>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3">
                                        <p class="text-gray-500">Unidades estimadas</p>
                                        <p id="purchase-quantity-preview" class="mt-1 text-lg font-semibold text-gray-900">0.000</p>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3">
                                        <p class="text-gray-500">Total estimado</p>
                                        <p id="purchase-total-preview" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-lg border border-gray-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Siguiente paso sugerido</p>
                                <p id="quick-next-step-title" class="mt-2 text-sm font-semibold text-gray-900">Completa al menos una línea.</p>
                                <p id="quick-next-step-description" class="mt-1 text-sm text-gray-600">Con una línea válida ya tendrás lectura suficiente para una compra rápida.</p>
                            </section>
                        </div>
                    </aside>
                </div>
            </form>
        </div>
    </div>

    <script type="application/json" id="variant-options">@json($variantOptions)</script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tableBody = document.querySelector('#purchase-items-table tbody');
            const addButton = document.getElementById('add-item');
            const linesPreview = document.getElementById('purchase-lines-preview');
            const quantityPreview = document.getElementById('purchase-quantity-preview');
            const totalPreview = document.getElementById('purchase-total-preview');
            const supplierSelect = document.getElementById('quick-supplier-id');
            const invoiceInput = document.getElementById('quick-invoice-number');
            const paymentTypeSelect = document.getElementById('quick-payment-type');
            const isCreditInput = document.getElementById('quick-is-credit');
            const contextCheck = document.getElementById('quick-check-context');
            const paymentCheck = document.getElementById('quick-check-payment');
            const linesCheck = document.getElementById('quick-check-lines');
            const modeTitle = document.getElementById('quick-mode-title');
            const modeDescription = document.getElementById('quick-mode-description');
            const nextStepTitle = document.getElementById('quick-next-step-title');
            const nextStepDescription = document.getElementById('quick-next-step-description');
            let index = tableBody.querySelectorAll('tr').length;
            const options = JSON.parse(document.getElementById('variant-options').textContent);

            const setTone = (element, tone) => {
                const tones = {
                    success: 'bg-emerald-100 text-emerald-700',
                    warning: 'bg-amber-100 text-amber-800',
                    danger: 'bg-red-100 text-red-700',
                    info: 'bg-emerald-100 text-emerald-700',
                    neutral: 'bg-slate-100 text-slate-700',
                };

                element.className = `rounded-full px-2.5 py-1 text-xs font-medium ${tones[tone] ?? tones.neutral}`;
            };

            function analyzeRow(row) {
                const variantId = row.querySelector('select[name*="[variant_id]"]')?.value;
                const quantity = Number(row.querySelector('input[name*="[quantity]"]')?.value || 0);
                const cost = Number(row.querySelector('input[name*="[unit_cost]"]')?.value || 0);
                const statusCell = row.querySelector('.quick-line-status');

                if (!variantId) {
                    statusCell.textContent = 'Selecciona una variante.';
                    statusCell.className = 'px-3 py-2 text-xs text-gray-600 quick-line-status';
                    return { ready: false, blocking: true, quantity: 0, amount: 0 };
                }

                if (quantity <= 0 || cost < 0) {
                    statusCell.textContent = 'Corrige cantidad y costo.';
                    statusCell.className = 'px-3 py-2 text-xs text-red-600 quick-line-status';
                    return { ready: false, blocking: true, quantity, amount: 0 };
                }

                if (cost === 0) {
                    statusCell.textContent = 'Costo en cero: revisa si esta compra sigue siendo simple.';
                    statusCell.className = 'px-3 py-2 text-xs text-amber-700 quick-line-status';
                    return { ready: true, warning: true, quantity, amount: quantity * cost };
                }

                statusCell.textContent = 'Línea lista para compra rápida.';
                statusCell.className = 'px-3 py-2 text-xs text-emerald-700 quick-line-status';
                return { ready: true, quantity, amount: quantity * cost };
            }

            function refreshSummary() {
                const rows = tableBody.querySelectorAll('tr');
                let totalQuantity = 0;
                let totalAmount = 0;
                let readyLines = 0;
                let warningLines = 0;
                let blockingLines = 0;

                rows.forEach((row) => {
                    const result = analyzeRow(row);
                    totalQuantity += result.quantity || 0;
                    totalAmount += result.amount || 0;
                    readyLines += result.ready ? 1 : 0;
                    warningLines += result.warning ? 1 : 0;
                    blockingLines += result.blocking ? 1 : 0;
                });

                linesPreview.textContent = rows.length;
                quantityPreview.textContent = totalQuantity.toFixed(3);
                totalPreview.textContent = `$${totalAmount.toFixed(2)}`;

                if (supplierSelect.value || invoiceInput.value.trim() !== '') {
                    setTone(contextCheck, 'info');
                    contextCheck.textContent = 'Contexto comercial cargado';
                } else {
                    setTone(contextCheck, 'neutral');
                    contextCheck.textContent = 'Mínimo operativo';
                }

                if (isCreditInput.checked) {
                    setTone(paymentCheck, 'warning');
                    paymentCheck.textContent = 'Crédito';
                } else if (paymentTypeSelect.value === 'transfer') {
                    setTone(paymentCheck, 'info');
                    paymentCheck.textContent = 'Transferencia';
                } else {
                    setTone(paymentCheck, 'success');
                    paymentCheck.textContent = 'Contado';
                }

                if (blockingLines > 0) {
                    setTone(linesCheck, 'danger');
                    linesCheck.textContent = 'Hay líneas incompletas';
                } else if (warningLines > 0) {
                    setTone(linesCheck, 'warning');
                    linesCheck.textContent = 'Líneas listas con revisión';
                } else if (readyLines > 0) {
                    setTone(linesCheck, 'success');
                    linesCheck.textContent = 'Líneas listas';
                } else {
                    setTone(linesCheck, 'neutral');
                    linesCheck.textContent = 'Pendientes';
                }

                if (warningLines > 0) {
                    modeTitle.textContent = 'Compra rápida con señales de que quizá necesitas modo detallado.';
                    modeDescription.textContent = 'Hay líneas con costo en cero o casos que merecen revisión. Si la compra dejó de ser simple, cambia de modo.';
                } else {
                    modeTitle.textContent = 'Compra rápida alineada con el caso simple.';
                    modeDescription.textContent = 'Úsala cuando no necesitas prorrateos, impuestos complejos ni líneas de bonificación separadas.';
                }

                if (blockingLines > 0) {
                    nextStepTitle.textContent = 'Completa las líneas pendientes antes de guardar.';
                    nextStepDescription.textContent = 'Sin variante y cantidad válidas no hay compra rápida consistente.';
                } else if (warningLines > 0) {
                    nextStepTitle.textContent = 'Revisa si este caso sigue siendo compra rápida.';
                    nextStepDescription.textContent = 'Al menos una línea sugiere que podrías necesitar la pantalla detallada para capturar mejor el caso.';
                } else if (readyLines > 0) {
                    nextStepTitle.textContent = 'La compra rápida ya tiene forma operativa.';
                    nextStepDescription.textContent = 'Si el contexto comercial es suficiente, puedes guardar sin ir al modo detallado.';
                } else {
                    nextStepTitle.textContent = 'Completa al menos una línea.';
                    nextStepDescription.textContent = 'Con una línea válida ya tendrás lectura suficiente para una compra rápida.';
                }
            }

            function bindRemoveButtons() {
                tableBody.querySelectorAll('.remove-line').forEach((button) => {
                    button.onclick = () => {
                        if (tableBody.querySelectorAll('tr').length > 1) {
                            button.closest('tr').remove();
                            refreshSummary();
                        }
                    };
                });

                tableBody.querySelectorAll('.quick-line-input').forEach((input) => {
                    input.oninput = refreshSummary;
                    input.onchange = refreshSummary;
                });
            }

            addButton.addEventListener('click', () => {
                const selectOptions = ['<option value="">Selecciona una variante</option>']
                    .concat(options.map(option => `<option value="${option.id}">${option.label}</option>`))
                    .join('');

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="px-3 py-2"><select name="items[${index}][variant_id]" class="quick-line-input block w-full rounded-md border-gray-300 shadow-sm" required>${selectOptions}</select></td>
                    <td class="px-3 py-2"><input name="items[${index}][quantity]" type="number" step="0.001" min="0.001" value="1" class="quick-line-input block w-32 rounded-md border-gray-300 shadow-sm" required></td>
                    <td class="px-3 py-2"><input name="items[${index}][unit_cost]" type="number" step="0.01" min="0" class="quick-line-input block w-36 rounded-md border-gray-300 shadow-sm" required></td>
                    <td class="px-3 py-2"><input name="items[${index}][expiration_date]" type="date" class="block w-40 rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2"><input name="items[${index}][notes]" type="text" class="block w-full rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2 text-xs text-gray-600 quick-line-status">Completa variante, cantidad y costo.</td>
                    <td class="px-3 py-2 text-right"><button type="button" class="remove-line text-red-600">Quitar</button></td>
                `;

                tableBody.appendChild(row);
                index += 1;
                bindRemoveButtons();
                refreshSummary();
            });

            [supplierSelect, invoiceInput, paymentTypeSelect, isCreditInput].forEach((input) => {
                input.addEventListener('input', refreshSummary);
                input.addEventListener('change', refreshSummary);
            });

            bindRemoveButtons();
            refreshSummary();
        });
    </script>
</x-app-layout>
