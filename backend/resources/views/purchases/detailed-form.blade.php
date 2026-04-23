@php
    use App\Support\Money;

    $isEditing = $purchase !== null;
    $variantOptions = $variants->map(function ($variant) {
        return [
            'id' => $variant->id,
            'label' => $variant->product->name . ' — ' . $variant->name,
            'tracks_expiration' => $variant->tracks_expiration,
        ];
    })->values();

    $defaultItems = $purchase?->items->map(function ($item) {
        return [
            'line_type' => $item->line_type,
            'variant_id' => $item->variant_id,
            'quantity' => (float) $item->quantity,
            'bonus_quantity' => (float) $item->bonus_quantity,
            'unit_cost' => $item->unit_cost_base_amount > 0 ? Money::centsToDollars($item->unit_cost_base_amount) : 0,
            'manual_total_cost' => $item->line_type === 'bonus' ? Money::centsToDollars($item->total_cost_amount) : 0,
            'line_discount_amount' => Money::centsToDollars($item->line_discount_amount),
            'tax_iva_amount' => Money::centsToDollars($item->tax_iva_amount),
            'tax_ice_amount' => Money::centsToDollars($item->tax_ice_amount),
            'tax_other_amount' => Money::centsToDollars($item->tax_other_amount),
            'eligible_for_global_iva' => $item->allocated_global_tax_iva_amount > 0,
            'eligible_for_global_ice' => $item->allocated_global_tax_ice_amount > 0,
            'eligible_for_global_other' => $item->allocated_global_tax_other_amount > 0,
            'expiration_date' => optional($item->expiration_date)->format('Y-m-d'),
            'notes' => $item->notes,
        ];
    })->toArray() ?? [[
        'line_type' => 'normal',
        'variant_id' => '',
        'quantity' => 1,
        'bonus_quantity' => 0,
        'unit_cost' => '',
        'manual_total_cost' => 0,
        'line_discount_amount' => 0,
        'tax_iva_amount' => 0,
        'tax_ice_amount' => 0,
        'tax_other_amount' => 0,
        'eligible_for_global_iva' => false,
        'eligible_for_global_ice' => false,
        'eligible_for_global_other' => false,
        'expiration_date' => '',
        'notes' => '',
    ]];

    $oldItems = old('items', $defaultItems);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">{{ $isEditing ? 'Editar compra detallada' : 'Nueva compra detallada' }}</h2>
                <p class="text-sm text-gray-500">Usa este modo cuando necesitas fidelidad: globales, bonificaciones separadas, descuentos e impuestos reproducibles.</p>
            </div>
            <a href="{{ route('purchases.create') }}" class="rounded-md border border-indigo-200 px-4 py-2 text-sm font-medium text-indigo-700">Volver a compra rápida</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Modo actual</p>
                            <h3 class="mt-1 text-base font-semibold text-indigo-900">Compra detallada = fidelidad y trazabilidad</h3>
                        </div>
                        <x-status-badge tone="info">Detallada</x-status-badge>
                    </div>
                    <ul class="mt-3 space-y-1 text-sm text-indigo-800">
                        <li>- descuentos e impuestos por línea</li>
                        <li>- bonificación del mismo producto o producto distinto</li>
                        <li>- globales y costos extra con lectura auditable</li>
                    </ul>
                </div>

                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Alternativa</p>
                            <h3 class="mt-1 text-base font-semibold text-emerald-900">Compra rápida = cuando el caso es simple</h3>
                        </div>
                        <x-status-badge tone="success">Rápida</x-status-badge>
                    </div>
                    <ul class="mt-3 space-y-1 text-sm text-emerald-800">
                        <li>- una línea por variante</li>
                        <li>- sin globales finos ni bonificación separada</li>
                        <li>- menor fricción para captura cotidiana</li>
                    </ul>
                </div>
            </div>

            <form method="POST" action="{{ $isEditing ? route('purchases.detailed.update', $purchase) : route('purchases.detailed.store') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @csrf
                @if($isEditing)
                    @method('PATCH')
                @endif

                <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                    <div class="order-2 space-y-6 xl:order-1">
                        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Proveedor</label>
                                <select id="detailed-supplier-id" name="supplier_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">Sin proveedor</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $purchase?->supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                @error('supplier_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Factura</label>
                                <input id="detailed-invoice-number" name="invoice_number" type="text" value="{{ old('invoice_number', $purchase?->invoice_number) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @error('invoice_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha</label>
                                <input name="purchased_at" type="datetime-local" value="{{ old('purchased_at', $purchase?->purchased_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                @error('purchased_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tipo de pago</label>
                                <select id="detailed-payment-type" name="payment_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="cash" @selected(old('payment_type', $purchase?->payment_type ?? 'cash') === 'cash')>Efectivo</option>
                                    <option value="transfer" @selected(old('payment_type', $purchase?->payment_type) === 'transfer')>Transferencia</option>
                                    <option value="credit" @selected(old('payment_type', $purchase?->payment_type) === 'credit')>Crédito</option>
                                </select>
                                @error('payment_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Descuento global</label>
                                <input name="global_discount_amount" type="number" step="0.01" min="0" value="{{ old('global_discount_amount', $purchase ? Money::centsToDollars($purchase->global_discount_amount) : 0) }}" class="global-summary-input mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">IVA global</label>
                                <input name="global_tax_iva_amount" type="number" step="0.01" min="0" value="{{ old('global_tax_iva_amount', $purchase ? Money::centsToDollars($purchase->global_tax_iva_amount) : 0) }}" class="global-summary-input mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">ICE global</label>
                                <input name="global_tax_ice_amount" type="number" step="0.01" min="0" value="{{ old('global_tax_ice_amount', $purchase ? Money::centsToDollars($purchase->global_tax_ice_amount) : 0) }}" class="global-summary-input mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Otro impuesto global</label>
                                <input name="global_tax_other_amount" type="number" step="0.01" min="0" value="{{ old('global_tax_other_amount', $purchase ? Money::centsToDollars($purchase->global_tax_other_amount) : 0) }}" class="global-summary-input mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Flete / otros costos</label>
                                <input name="extra_costs_amount" type="number" step="0.01" min="0" value="{{ old('extra_costs_amount', $purchase ? Money::centsToDollars($purchase->extra_costs_amount) : 0) }}" class="global-summary-input mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notas</label>
                            <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes', $purchase?->notes) }}</textarea>
                        </div>

                        <div class="grid gap-4 rounded-lg border border-indigo-100 bg-indigo-50 p-4 lg:grid-cols-3 text-sm">
                            <div>
                                <p class="font-medium text-indigo-900">Cómo leer el costo estimado</p>
                                <p class="mt-1 text-indigo-800">Cada línea muestra base, descuentos, impuestos de línea y un costo estimado final antes de guardar.</p>
                            </div>
                            <div>
                                <p class="font-medium text-indigo-900">Líneas normales</p>
                                <p class="mt-1 text-indigo-800">Usa cantidad, bonus del mismo producto, costo base y tributos directos. El sistema reparte globales después.</p>
                            </div>
                            <div>
                                <p class="font-medium text-indigo-900">Líneas bonificación</p>
                                <p class="mt-1 text-indigo-800">Sirven para producto distinto. Puedes dejar costo 0 o asignar un costo total manual.</p>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between bg-gray-50 px-4 py-3">
                                <div>
                                    <h3 class="font-medium text-gray-800">Líneas detalladas</h3>
                                    <p class="mt-1 text-sm text-gray-500">Usa línea normal para compra pagada y línea bonificación para producto distinto con costo manual o costo 0.</p>
                                </div>
                                <button type="button" id="add-item" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white">Agregar línea</button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm" id="purchase-items-table">
                                    <thead class="bg-gray-50 text-gray-500">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Tipo</th>
                                            <th class="px-3 py-2 text-left">Variante</th>
                                            <th class="px-3 py-2 text-left">Cantidad</th>
                                            <th class="px-3 py-2 text-left">Bonus mismo prod.</th>
                                            <th class="px-3 py-2 text-left">Costo base</th>
                                            <th class="px-3 py-2 text-left">Costo manual</th>
                                            <th class="px-3 py-2 text-left">Descuento</th>
                                            <th class="px-3 py-2 text-left">IVA</th>
                                            <th class="px-3 py-2 text-left">ICE</th>
                                            <th class="px-3 py-2 text-left">Otro</th>
                                            <th class="px-3 py-2 text-left">Elegible global</th>
                                            <th class="px-3 py-2 text-left">Vencimiento</th>
                                            <th class="px-3 py-2 text-left">Notas</th>
                                            <th class="px-3 py-2 text-left">Desglose</th>
                                            <th class="px-3 py-2 text-left">Costo estimado</th>
                                            <th class="px-3 py-2 text-left">Estado</th>
                                            <th class="px-3 py-2 text-right">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($oldItems as $index => $item)
                                            <tr class="align-top">
                                                <td class="px-3 py-2">
                                                    <select name="items[{{ $index }}][line_type]" class="line-type block w-32 rounded-md border-gray-300 shadow-sm">
                                                        <option value="normal" @selected(($item['line_type'] ?? 'normal') === 'normal')>Normal</option>
                                                        <option value="bonus" @selected(($item['line_type'] ?? 'normal') === 'bonus')>Bonificación</option>
                                                    </select>
                                                    @error("items.$index.line_type")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                                </td>
                                                <td class="px-3 py-2">
                                                    <select name="items[{{ $index }}][variant_id]" class="block w-56 rounded-md border-gray-300 shadow-sm" required>
                                                        <option value="">Selecciona una variante</option>
                                                        @foreach ($variants as $variant)
                                                            <option value="{{ $variant->id }}" @selected((string) ($item['variant_id'] ?? '') === (string) $variant->id)>{{ $variant->product->name }} — {{ $variant->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error("items.$index.variant_id")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input name="items[{{ $index }}][quantity]" type="number" step="0.001" min="0.001" value="{{ $item['quantity'] ?? 1 }}" class="line-input block w-28 rounded-md border-gray-300 shadow-sm" required>
                                                    @error("items.$index.quantity")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input name="items[{{ $index }}][bonus_quantity]" type="number" step="0.001" min="0" value="{{ $item['bonus_quantity'] ?? 0 }}" class="line-input normal-only block w-28 rounded-md border-gray-300 shadow-sm">
                                                    @error("items.$index.bonus_quantity")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input name="items[{{ $index }}][unit_cost]" type="number" step="0.01" min="0" value="{{ $item['unit_cost'] ?? '' }}" class="line-input normal-only block w-28 rounded-md border-gray-300 shadow-sm">
                                                    @error("items.$index.unit_cost")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input name="items[{{ $index }}][manual_total_cost]" type="number" step="0.01" min="0" value="{{ $item['manual_total_cost'] ?? 0 }}" class="line-input bonus-only block w-28 rounded-md border-gray-300 shadow-sm">
                                                    @error("items.$index.manual_total_cost")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                                </td>
                                                <td class="px-3 py-2"><input name="items[{{ $index }}][line_discount_amount]" type="number" step="0.01" min="0" value="{{ $item['line_discount_amount'] ?? 0 }}" class="line-input normal-only block w-24 rounded-md border-gray-300 shadow-sm"></td>
                                                <td class="px-3 py-2"><input name="items[{{ $index }}][tax_iva_amount]" type="number" step="0.01" min="0" value="{{ $item['tax_iva_amount'] ?? 0 }}" class="line-input block w-24 rounded-md border-gray-300 shadow-sm"></td>
                                                <td class="px-3 py-2"><input name="items[{{ $index }}][tax_ice_amount]" type="number" step="0.01" min="0" value="{{ $item['tax_ice_amount'] ?? 0 }}" class="line-input block w-24 rounded-md border-gray-300 shadow-sm"></td>
                                                <td class="px-3 py-2"><input name="items[{{ $index }}][tax_other_amount]" type="number" step="0.01" min="0" value="{{ $item['tax_other_amount'] ?? 0 }}" class="line-input block w-24 rounded-md border-gray-300 shadow-sm"></td>
                                                <td class="px-3 py-2">
                                                    <div class="space-y-1 text-xs text-gray-600">
                                                        <label class="flex items-center gap-1"><input type="checkbox" name="items[{{ $index }}][eligible_for_global_iva]" value="1" class="line-input" @checked(($item['eligible_for_global_iva'] ?? false))> IVA</label>
                                                        <label class="flex items-center gap-1"><input type="checkbox" name="items[{{ $index }}][eligible_for_global_ice]" value="1" class="line-input" @checked(($item['eligible_for_global_ice'] ?? false))> ICE</label>
                                                        <label class="flex items-center gap-1"><input type="checkbox" name="items[{{ $index }}][eligible_for_global_other]" value="1" class="line-input" @checked(($item['eligible_for_global_other'] ?? false))> Otro</label>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2"><input name="items[{{ $index }}][expiration_date]" type="date" value="{{ $item['expiration_date'] ?? '' }}" class="block w-36 rounded-md border-gray-300 shadow-sm"></td>
                                                <td class="px-3 py-2"><input name="items[{{ $index }}][notes]" type="text" value="{{ $item['notes'] ?? '' }}" class="block w-48 rounded-md border-gray-300 shadow-sm"></td>
                                                <td class="px-3 py-2 text-xs text-gray-600 line-breakdown">
                                                    <p>Base: <span class="line-gross font-medium text-gray-800">$0.00</span></p>
                                                    <p>Desc.: <span class="line-discount font-medium text-gray-800">$0.00</span></p>
                                                    <p>Imp.: <span class="line-taxes font-medium text-gray-800">$0.00</span></p>
                                                    <p>Recibe: <span class="line-received font-medium text-gray-800">0.000</span></p>
                                                </td>
                                                <td class="px-3 py-2 text-sm font-medium text-gray-700 estimated-line-cost">$0.00</td>
                                                <td class="px-3 py-2 text-xs text-gray-600 line-status">Completa la línea para calcularla.</td>
                                                <td class="px-3 py-2 text-right"><button type="button" class="remove-line text-red-600">Quitar</button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if ($errors->any())
                            <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                                <p class="font-medium">Revisa los datos ingresados. Hay errores en la compra detallada.</p>
                                <ul class="mt-2 list-disc space-y-1 pl-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('purchases.index') }}" class="text-sm text-gray-600">Cancelar</a>
                            <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">{{ $isEditing ? 'Actualizar compra detallada' : 'Guardar compra detallada' }}</button>
                        </div>
                    </div>

                    <aside class="order-1 xl:order-2">
                        <div class="space-y-4 xl:sticky xl:top-6">
                            <section class="rounded-lg border border-gray-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado operativo</p>
                                <p id="detailed-status-title" class="mt-2 text-base font-semibold text-slate-900">Arma la compra con suficiente contexto comercial.</p>
                                <p id="detailed-status-description" class="mt-1 text-sm text-slate-600">Primero valida proveedor, forma de pago y calidad de líneas; luego interpreta el total financiero.</p>
                                <div class="mt-4 space-y-2 text-sm">
                                    <div class="flex items-center justify-between gap-3"><span class="text-slate-600">Proveedor / factura</span><span id="detailed-check-context" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">Contexto mínimo</span></div>
                                    <div class="flex items-center justify-between gap-3"><span class="text-slate-600">Líneas</span><span id="detailed-check-lines" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">Pendientes</span></div>
                                    <div class="flex items-center justify-between gap-3"><span class="text-slate-600">Globales</span><span id="detailed-check-globals" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">Sin globales</span></div>
                                    <div class="flex items-center justify-between gap-3"><span class="text-slate-600">Tipo de pago</span><span id="detailed-check-payment" class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">Contado</span></div>
                                </div>
                            </section>

                            <section class="rounded-lg border border-gray-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lectura del resumen</p>
                                <div class="mt-3 grid gap-3 text-sm">
                                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-gray-500">Líneas</p><p id="summary-lines" class="mt-1 text-lg font-semibold text-gray-900">0</p></div>
                                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-gray-500">Subtotal</p><p id="summary-subtotal" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p></div>
                                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-gray-500">Impuestos líneas</p><p id="summary-line-taxes" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p></div>
                                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-gray-500">Globales</p><p id="summary-globals" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p></div>
                                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-gray-500">Unidades recibidas</p><p id="summary-quantity" class="mt-1 text-lg font-semibold text-gray-900">0.000</p></div>
                                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-gray-500">Total estimado</p><p id="summary-total" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p></div>
                                </div>
                            </section>

                            <section class="rounded-lg border border-gray-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tipo de líneas</p>
                                <div class="mt-3 grid gap-3 sm:grid-cols-3 xl:grid-cols-1 text-sm">
                                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-gray-500">Normales</p><p id="detailed-normal-lines" class="mt-1 text-base font-semibold text-gray-900">0</p></div>
                                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-gray-500">Bonificación</p><p id="detailed-bonus-lines" class="mt-1 text-base font-semibold text-gray-900">0</p></div>
                                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-gray-500">Atención</p><p id="detailed-attention-lines" class="mt-1 text-base font-semibold text-gray-900">0</p></div>
                                </div>
                            </section>

                            <section class="rounded-lg border border-gray-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Siguiente paso sugerido</p>
                                <p id="detailed-next-step-title" class="mt-2 text-sm font-semibold text-gray-900">Completa líneas y contexto comercial.</p>
                                <p id="detailed-next-step-description" class="mt-1 text-sm text-gray-600">Cuando las líneas estén consistentes, podrás interpretar el total con criterio y guardar.</p>
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
            const variantOptions = JSON.parse(document.getElementById('variant-options').textContent);
            const supplierSelect = document.getElementById('detailed-supplier-id');
            const invoiceInput = document.getElementById('detailed-invoice-number');
            const paymentTypeSelect = document.getElementById('detailed-payment-type');
            const statusTitle = document.getElementById('detailed-status-title');
            const statusDescription = document.getElementById('detailed-status-description');
            const contextCheck = document.getElementById('detailed-check-context');
            const linesCheck = document.getElementById('detailed-check-lines');
            const globalsCheck = document.getElementById('detailed-check-globals');
            const paymentCheck = document.getElementById('detailed-check-payment');
            const normalLines = document.getElementById('detailed-normal-lines');
            const bonusLines = document.getElementById('detailed-bonus-lines');
            const attentionLines = document.getElementById('detailed-attention-lines');
            const nextStepTitle = document.getElementById('detailed-next-step-title');
            const nextStepDescription = document.getElementById('detailed-next-step-description');
            let index = tableBody.querySelectorAll('tr').length;

            const formatMoney = (amount) => `$${amount.toFixed(2)}`;
            const numberValue = (input) => Number(input?.value || 0);
            const setTone = (element, tone) => {
                const tones = {
                    success: 'bg-emerald-100 text-emerald-700',
                    warning: 'bg-amber-100 text-amber-800',
                    danger: 'bg-red-100 text-red-700',
                    info: 'bg-indigo-100 text-indigo-700',
                    neutral: 'bg-slate-100 text-slate-700',
                };
                element.className = `rounded-full px-2.5 py-1 text-xs font-medium ${tones[tone] ?? tones.neutral}`;
            };

            function toggleRowMode(row) {
                const lineType = row.querySelector('.line-type')?.value ?? 'normal';
                row.querySelectorAll('.normal-only').forEach((input) => {
                    input.disabled = lineType !== 'normal';
                    if (lineType !== 'normal') {
                        input.value = input.name.includes('[bonus_quantity]') ? 0 : '';
                    }
                });
                row.querySelectorAll('.bonus-only').forEach((input) => {
                    input.disabled = lineType !== 'bonus';
                    if (lineType !== 'bonus') {
                        input.value = 0;
                    }
                });
            }

            function recalculateRow(row) {
                const lineType = row.querySelector('.line-type')?.value ?? 'normal';
                const quantity = numberValue(row.querySelector('input[name*="[quantity]"]'));
                const bonusQuantity = lineType === 'normal' ? numberValue(row.querySelector('input[name*="[bonus_quantity]"]')) : 0;
                const unitCost = lineType === 'normal' ? numberValue(row.querySelector('input[name*="[unit_cost]"]')) : 0;
                const manualTotalCost = lineType === 'bonus' ? numberValue(row.querySelector('input[name*="[manual_total_cost]"]')) : 0;
                const discount = lineType === 'normal' ? numberValue(row.querySelector('input[name*="[line_discount_amount]"]')) : 0;
                const lineIva = numberValue(row.querySelector('input[name*="[tax_iva_amount]"]'));
                const lineIce = numberValue(row.querySelector('input[name*="[tax_ice_amount]"]'));
                const lineOther = numberValue(row.querySelector('input[name*="[tax_other_amount]"]'));
                const statusCell = row.querySelector('.line-status');

                const gross = lineType === 'bonus' ? manualTotalCost : quantity * unitCost;
                const finalAmount = gross - discount + lineIva + lineIce + lineOther;
                row.querySelector('.estimated-line-cost').textContent = formatMoney(Math.max(finalAmount, 0));
                row.querySelector('.line-gross').textContent = formatMoney(Math.max(gross, 0));
                row.querySelector('.line-discount').textContent = formatMoney(Math.max(discount, 0));
                row.querySelector('.line-taxes').textContent = formatMoney(Math.max(lineIva + lineIce + lineOther, 0));
                row.querySelector('.line-received').textContent = (lineType === 'bonus' ? quantity : quantity + bonusQuantity).toFixed(3);

                let attention = false;
                if (!row.querySelector('select[name*="[variant_id]"]')?.value || quantity <= 0) {
                    statusCell.textContent = 'Completa variante y cantidad.';
                    statusCell.className = 'px-3 py-2 text-xs text-red-600 line-status';
                    attention = true;
                } else if (lineType === 'bonus' && manualTotalCost === 0) {
                    statusCell.textContent = 'Bonificación válida, revisa si el costo debe quedar en cero.';
                    statusCell.className = 'px-3 py-2 text-xs text-amber-700 line-status';
                    attention = true;
                } else if (lineType === 'normal' && unitCost === 0) {
                    statusCell.textContent = 'Costo base en cero: revisa si el caso sigue siendo correcto.';
                    statusCell.className = 'px-3 py-2 text-xs text-amber-700 line-status';
                    attention = true;
                } else {
                    statusCell.textContent = lineType === 'bonus' ? 'Línea bonificación lista.' : 'Línea normal lista.';
                    statusCell.className = 'px-3 py-2 text-xs text-emerald-700 line-status';
                }

                return {
                    lineType,
                    quantity,
                    bonusQuantity,
                    receivedQuantity: lineType === 'bonus' ? quantity : quantity + bonusQuantity,
                    gross,
                    lineTaxes: lineIva + lineIce + lineOther,
                    finalAmount: Math.max(finalAmount, 0),
                    attention,
                };
            }

            function refreshSummary() {
                const rows = tableBody.querySelectorAll('tr');
                const globalDiscount = numberValue(document.querySelector('input[name="global_discount_amount"]'));
                const globalTaxes = numberValue(document.querySelector('input[name="global_tax_iva_amount"]'))
                    + numberValue(document.querySelector('input[name="global_tax_ice_amount"]'))
                    + numberValue(document.querySelector('input[name="global_tax_other_amount"]'));
                const extraCosts = numberValue(document.querySelector('input[name="extra_costs_amount"]'));

                let subtotal = 0;
                let lineTaxes = 0;
                let receivedQuantity = 0;
                let normalCount = 0;
                let bonusCount = 0;
                let attentionCount = 0;

                rows.forEach((row) => {
                    const rowData = recalculateRow(row);
                    subtotal += rowData.gross;
                    lineTaxes += rowData.lineTaxes;
                    receivedQuantity += rowData.receivedQuantity;
                    normalCount += rowData.lineType === 'normal' ? 1 : 0;
                    bonusCount += rowData.lineType === 'bonus' ? 1 : 0;
                    attentionCount += rowData.attention ? 1 : 0;
                });

                const total = subtotal - globalDiscount + lineTaxes + globalTaxes + extraCosts;

                document.getElementById('summary-lines').textContent = rows.length;
                document.getElementById('summary-subtotal').textContent = formatMoney(subtotal);
                document.getElementById('summary-line-taxes').textContent = formatMoney(lineTaxes);
                document.getElementById('summary-globals').textContent = formatMoney(globalTaxes + extraCosts - globalDiscount);
                document.getElementById('summary-quantity').textContent = receivedQuantity.toFixed(3);
                document.getElementById('summary-total').textContent = formatMoney(Math.max(total, 0));
                normalLines.textContent = String(normalCount);
                bonusLines.textContent = String(bonusCount);
                attentionLines.textContent = String(attentionCount);

                if (supplierSelect.value || invoiceInput.value.trim() !== '') {
                    setTone(contextCheck, 'info');
                    contextCheck.textContent = 'Contexto comercial cargado';
                } else {
                    setTone(contextCheck, 'neutral');
                    contextCheck.textContent = 'Contexto mínimo';
                }

                if (attentionCount > 0) {
                    setTone(linesCheck, 'warning');
                    linesCheck.textContent = 'Líneas con revisión';
                } else if (rows.length > 0) {
                    setTone(linesCheck, 'success');
                    linesCheck.textContent = 'Líneas consistentes';
                } else {
                    setTone(linesCheck, 'neutral');
                    linesCheck.textContent = 'Pendientes';
                }

                if (globalDiscount > 0 || globalTaxes > 0 || extraCosts > 0) {
                    setTone(globalsCheck, 'info');
                    globalsCheck.textContent = 'Globales activos';
                } else {
                    setTone(globalsCheck, 'neutral');
                    globalsCheck.textContent = 'Sin globales';
                }

                if (paymentTypeSelect.value === 'credit') {
                    setTone(paymentCheck, 'warning');
                    paymentCheck.textContent = 'Crédito';
                } else if (paymentTypeSelect.value === 'transfer') {
                    setTone(paymentCheck, 'info');
                    paymentCheck.textContent = 'Transferencia';
                } else {
                    setTone(paymentCheck, 'success');
                    paymentCheck.textContent = 'Contado';
                }

                if (attentionCount > 0) {
                    statusTitle.textContent = 'La compra detallada aún requiere revisión de líneas.';
                    statusDescription.textContent = 'Antes de confiar en el total, corrige líneas con costo dudoso, bonificación en cero o datos incompletos.';
                    nextStepTitle.textContent = 'Revisa primero las líneas marcadas con atención.';
                    nextStepDescription.textContent = 'La compra detallada solo tiene sentido si cada línea representa bien el caso real.';
                } else if (globalDiscount > 0 || globalTaxes > 0 || extraCosts > 0) {
                    statusTitle.textContent = 'La compra ya refleja impacto fino de globales.';
                    statusDescription.textContent = 'Ahora puedes leer el total como resultado combinado de líneas, globales y costos extra.';
                    nextStepTitle.textContent = 'Verifica que los globales representen el comprobante real.';
                    nextStepDescription.textContent = 'Si el contexto comercial ya está correcto, puedes guardar con buena fidelidad.';
                } else {
                    statusTitle.textContent = 'La compra detallada está estructurada de forma simple.';
                    statusDescription.textContent = 'Todavía no hay globales activos; si no los necesitas, quizá este caso también podría resolverse en modo rápido.';
                    nextStepTitle.textContent = 'Decide si mantienes este caso en modo detallado.';
                    nextStepDescription.textContent = 'Si el caso requiere fidelidad fina, sigue aquí; si no, el modo rápido habría sido suficiente.';
                }
            }

            function bindRow(row) {
                row.querySelector('.line-type')?.addEventListener('change', () => {
                    toggleRowMode(row);
                    refreshSummary();
                });

                row.querySelectorAll('.line-input, input[type="date"], input[type="text"], select').forEach((input) => {
                    input.addEventListener('input', refreshSummary);
                    input.addEventListener('change', refreshSummary);
                });

                row.querySelector('.remove-line')?.addEventListener('click', () => {
                    if (tableBody.querySelectorAll('tr').length > 1) {
                        row.remove();
                        refreshSummary();
                    }
                });

                toggleRowMode(row);
            }

            addButton.addEventListener('click', () => {
                const variantSelectOptions = ['<option value="">Selecciona una variante</option>']
                    .concat(variantOptions.map(option => `<option value="${option.id}">${option.label}</option>`))
                    .join('');

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="px-3 py-2"><select name="items[${index}][line_type]" class="line-type block w-32 rounded-md border-gray-300 shadow-sm"><option value="normal">Normal</option><option value="bonus">Bonificación</option></select></td>
                    <td class="px-3 py-2"><select name="items[${index}][variant_id]" class="block w-56 rounded-md border-gray-300 shadow-sm" required>${variantSelectOptions}</select></td>
                    <td class="px-3 py-2"><input name="items[${index}][quantity]" type="number" step="0.001" min="0.001" value="1" class="line-input block w-28 rounded-md border-gray-300 shadow-sm" required></td>
                    <td class="px-3 py-2"><input name="items[${index}][bonus_quantity]" type="number" step="0.001" min="0" value="0" class="line-input normal-only block w-28 rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2"><input name="items[${index}][unit_cost]" type="number" step="0.01" min="0" class="line-input normal-only block w-28 rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2"><input name="items[${index}][manual_total_cost]" type="number" step="0.01" min="0" value="0" class="line-input bonus-only block w-28 rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2"><input name="items[${index}][line_discount_amount]" type="number" step="0.01" min="0" value="0" class="line-input normal-only block w-24 rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2"><input name="items[${index}][tax_iva_amount]" type="number" step="0.01" min="0" value="0" class="line-input block w-24 rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2"><input name="items[${index}][tax_ice_amount]" type="number" step="0.01" min="0" value="0" class="line-input block w-24 rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2"><input name="items[${index}][tax_other_amount]" type="number" step="0.01" min="0" value="0" class="line-input block w-24 rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2"><div class="space-y-1 text-xs text-gray-600"><label class="flex items-center gap-1"><input type="checkbox" name="items[${index}][eligible_for_global_iva]" value="1" class="line-input"> IVA</label><label class="flex items-center gap-1"><input type="checkbox" name="items[${index}][eligible_for_global_ice]" value="1" class="line-input"> ICE</label><label class="flex items-center gap-1"><input type="checkbox" name="items[${index}][eligible_for_global_other]" value="1" class="line-input"> Otro</label></div></td>
                    <td class="px-3 py-2"><input name="items[${index}][expiration_date]" type="date" class="block w-36 rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2"><input name="items[${index}][notes]" type="text" class="block w-48 rounded-md border-gray-300 shadow-sm"></td>
                    <td class="px-3 py-2 text-xs text-gray-600 line-breakdown"><p>Base: <span class="line-gross font-medium text-gray-800">$0.00</span></p><p>Desc.: <span class="line-discount font-medium text-gray-800">$0.00</span></p><p>Imp.: <span class="line-taxes font-medium text-gray-800">$0.00</span></p><p>Recibe: <span class="line-received font-medium text-gray-800">0.000</span></p></td>
                    <td class="px-3 py-2 text-sm font-medium text-gray-700 estimated-line-cost">$0.00</td>
                    <td class="px-3 py-2 text-xs text-gray-600 line-status">Completa la línea para calcularla.</td>
                    <td class="px-3 py-2 text-right"><button type="button" class="remove-line text-red-600">Quitar</button></td>
                `;

                tableBody.appendChild(row);
                bindRow(row);
                index += 1;
                refreshSummary();
            });

            document.querySelectorAll('.global-summary-input, #detailed-supplier-id, #detailed-invoice-number, #detailed-payment-type').forEach((input) => {
                input.addEventListener('input', refreshSummary);
                input.addEventListener('change', refreshSummary);
            });

            tableBody.querySelectorAll('tr').forEach(bindRow);
            refreshSummary();
        });
    </script>
</x-app-layout>
