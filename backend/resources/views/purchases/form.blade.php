@php
    $variantOptions = $variants->map(function ($variant) {
        return [
            'id' => $variant->id,
            'label' => $variant->product->name . ' — ' . $variant->name,
        ];
    })->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800">Nueva compra rápida</h2>
            <a href="{{ route('purchases.detailed.create') }}" class="rounded-md border border-indigo-200 px-4 py-2 text-sm font-medium text-indigo-700">Ir a compra detallada</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('purchases.store') }}" class="space-y-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @csrf

                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Proveedor</label>
                        <select name="supplier_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Sin proveedor</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Factura</label>
                        <input name="invoice_number" type="text" value="{{ old('invoice_number') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha</label>
                        <input name="purchased_at" type="datetime-local" value="{{ old('purchased_at', now()->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo de pago</label>
                        <select name="payment_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="cash">Efectivo</option>
                            <option value="transfer">Transferencia</option>
                        </select>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_credit" value="1" @checked(old('is_credit'))>
                    Compra a crédito
                </label>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Notas</label>
                    <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes') }}</textarea>
                </div>

                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between bg-gray-50 px-4 py-3">
                        <h3 class="font-medium text-gray-800">Líneas de compra</h3>
                        <button type="button" id="add-item" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white">Agregar línea</button>
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
                                    <th class="px-3 py-2 text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($oldItems = old('items', [['variant_id' => '', 'quantity' => 1, 'unit_cost' => '', 'expiration_date' => '', 'notes' => '']]))
                                @foreach ($oldItems as $index => $item)
                                    <tr>
                                        <td class="px-3 py-2">
                                            <select name="items[{{ $index }}][variant_id]" class="block w-full rounded-md border-gray-300 shadow-sm" required>
                                                <option value="">Selecciona una variante</option>
                                                @foreach ($variants as $variant)
                                                    <option value="{{ $variant->id }}" @selected((string) ($item['variant_id'] ?? '') === (string) $variant->id)>{{ $variant->product->name }} — {{ $variant->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-3 py-2"><input name="items[{{ $index }}][quantity]" type="number" step="0.001" min="0.001" value="{{ $item['quantity'] ?? 1 }}" class="block w-32 rounded-md border-gray-300 shadow-sm" required></td>
                                        <td class="px-3 py-2"><input name="items[{{ $index }}][unit_cost]" type="number" step="0.01" min="0" value="{{ $item['unit_cost'] ?? '' }}" class="block w-36 rounded-md border-gray-300 shadow-sm" required></td>
                                        <td class="px-3 py-2"><input name="items[{{ $index }}][expiration_date]" type="date" value="{{ $item['expiration_date'] ?? '' }}" class="block w-40 rounded-md border-gray-300 shadow-sm"></td>
                                        <td class="px-3 py-2"><input name="items[{{ $index }}][notes]" type="text" value="{{ $item['notes'] ?? '' }}" class="block w-full rounded-md border-gray-300 shadow-sm"></td>
                                        <td class="px-3 py-2 text-right"><button type="button" class="remove-line text-red-600">Quitar</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid gap-4 rounded-lg bg-gray-50 p-4 md:grid-cols-3 text-sm">
                    <div>
                        <p class="text-gray-500">Líneas</p>
                        <p id="purchase-lines-preview" class="mt-1 text-lg font-semibold text-gray-900">0</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Unidades estimadas</p>
                        <p id="purchase-quantity-preview" class="mt-1 text-lg font-semibold text-gray-900">0.000</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Total estimado</p>
                        <p id="purchase-total-preview" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                        Revisa los datos ingresados. Hay errores en el formulario.
                    </div>
                @endif

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('purchases.index') }}" class="text-sm text-gray-600">Cancelar</a>
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Guardar compra</button>
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
            let index = tableBody.querySelectorAll('tr').length;
            const options = JSON.parse(document.getElementById('variant-options').textContent);

            function refreshSummary() {
                const rows = tableBody.querySelectorAll('tr');
                let totalQuantity = 0;
                let totalAmount = 0;

                rows.forEach((row) => {
                    const quantityInput = row.querySelector('input[name*="[quantity]"]');
                    const costInput = row.querySelector('input[name*="[unit_cost]"]');
                    const quantity = Number(quantityInput?.value || 0);
                    const cost = Number(costInput?.value || 0);

                    totalQuantity += quantity;
                    totalAmount += quantity * cost;
                });

                linesPreview.textContent = rows.length;
                quantityPreview.textContent = totalQuantity.toFixed(3);
                totalPreview.textContent = `$${totalAmount.toFixed(2)}`;
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

                tableBody.querySelectorAll('input[name*="[quantity]"], input[name*="[unit_cost]"]').forEach((input) => {
                    input.oninput = refreshSummary;
                });
            }

            addButton.addEventListener('click', () => {
                const row = document.createElement('tr');
                const selectOptions = ['<option value="">Selecciona una variante</option>']
                    .concat(options.map(option => `<option value="${option.id}">${option.label}</option>`))
                    .join('');

                row.innerHTML = `
                    <td class="px-3 py-2"><select name="items[${index}][variant_id]" class="block w-full rounded-md border-gray-300 shadow-sm" required>${selectOptions}</select></td>
                    <td class="px-3 py-2"><input name="items[${index}][quantity]" type="number" step="0.001" min="0.001" value="1" class="block w-32 rounded-md border-gray-300 shadow-sm" required></td>
                    <td class="px-3 py-2"><input name="items[${index}][unit_cost]" type="number" step="0.01" min="0" class="block w-36 rounded-md border-gray-300 shadow-sm" required></td>
                    <td class="px-3 py-2"><input name="items[${index}][expiration_date]" type="date" class="block w-40 rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2"><input name="items[${index}][notes]" type="text" class="block w-full rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2 text-right"><button type="button" class="remove-line text-red-600">Quitar</button></td>
                `;

                tableBody.appendChild(row);
                index += 1;
                bindRemoveButtons();
                refreshSummary();
            });

            bindRemoveButtons();
            refreshSummary();
        });
    </script>
</x-app-layout>
