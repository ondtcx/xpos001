@php
    $variantOptions = $variants->map(fn ($variant) => [
        'id' => $variant->id,
        'label' => $variant->product->name . ' — ' . $variant->name,
    ])->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Registrar inventario inicial</h2>
                <p class="text-sm text-gray-500">Carga stock de arranque con claridad sobre auditoría, lote generado y siguiente paso operativo.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('inventory-lots.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Ver lotes</a>
                <a href="{{ route('opening-inventory.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Ver historial</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('opening-inventory.store') }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @csrf

                <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <div class="order-2 space-y-6 xl:order-1">
                        <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                            Usa este formulario para regularizar stock por tandas. Si todavía no auditaste el conteo, deja el registro sin marcar como auditado para distinguirlo de compras reales.
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Variante</label>
                            <select id="opening-variant-id" name="variant_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="">Selecciona una variante</option>
                                @foreach ($variants as $variant)
                                    <option value="{{ $variant->id }}" @selected((string) old('variant_id') === (string) $variant->id)>{{ $variant->product->name }} — {{ $variant->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid gap-6 md:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Cantidad</label>
                                <input id="opening-quantity" name="quantity" type="number" step="0.001" min="0.001" value="{{ old('quantity') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Costo estimado unitario</label>
                                <input id="opening-cost" name="estimated_unit_cost" type="number" step="0.01" min="0" value="{{ old('estimated_unit_cost') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha</label>
                                <input name="recorded_at" type="datetime-local" value="{{ old('recorded_at', now()->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                        </div>

                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input id="opening-is-audited" type="checkbox" name="is_audited" value="1" @checked(old('is_audited'))>
                            Marcar como auditado
                        </label>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notas</label>
                            <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes') }}</textarea>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('opening-inventory.index') }}" class="text-sm text-gray-600">Cancelar</a>
                            <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Guardar inventario inicial</button>
                        </div>
                    </div>

                    <aside class="order-1 xl:order-2">
                        <div class="space-y-4 xl:sticky xl:top-6">
                            <section class="rounded-lg border border-gray-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado operativo</p>
                                <p id="opening-status-title" class="mt-2 text-base font-semibold text-slate-900">Completa la variante y la cantidad.</p>
                                <p id="opening-status-description" class="mt-1 text-sm text-slate-600">El sistema generará un lote y un movimiento de apertura apenas guardes.</p>
                                <div class="mt-4 space-y-2 text-sm">
                                    <div class="flex items-center justify-between gap-3"><span class="text-slate-600">Variante</span><span id="opening-check-variant" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">Pendiente</span></div>
                                    <div class="flex items-center justify-between gap-3"><span class="text-slate-600">Conteo</span><span id="opening-check-quantity" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">Pendiente</span></div>
                                    <div class="flex items-center justify-between gap-3"><span class="text-slate-600">Auditoría</span><span id="opening-check-audit" class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">Estimado</span></div>
                                </div>
                            </section>

                            <section class="rounded-lg border border-gray-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Resumen monetario</p>
                                <div class="mt-3 grid gap-3 text-sm">
                                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-gray-500">Costo unitario estimado</p><p id="opening-cost-preview" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p></div>
                                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-gray-500">Valor total estimado</p><p id="opening-total-preview" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p></div>
                                    <div class="rounded-lg bg-gray-50 p-3"><p class="text-gray-500">Cantidad a cargar</p><p id="opening-quantity-preview" class="mt-1 text-lg font-semibold text-gray-900">0.000</p></div>
                                </div>
                            </section>

                            <section class="rounded-lg border border-gray-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Siguiente paso sugerido</p>
                                <p id="opening-next-step-title" class="mt-2 text-sm font-semibold text-gray-900">Completa datos mínimos.</p>
                                <p id="opening-next-step-description" class="mt-1 text-sm text-gray-600">Cuando el registro esté consistente, podrás guardarlo y luego revisar el lote generado.</p>
                            </section>
                        </div>
                    </aside>
                </div>
            </form>
        </div>
    </div>

    <script type="application/json" id="opening-variant-options">@json($variantOptions)</script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const variantSelect = document.getElementById('opening-variant-id');
            const quantityInput = document.getElementById('opening-quantity');
            const costInput = document.getElementById('opening-cost');
            const auditedInput = document.getElementById('opening-is-audited');
            const costPreview = document.getElementById('opening-cost-preview');
            const totalPreview = document.getElementById('opening-total-preview');
            const quantityPreview = document.getElementById('opening-quantity-preview');
            const statusTitle = document.getElementById('opening-status-title');
            const statusDescription = document.getElementById('opening-status-description');
            const checkVariant = document.getElementById('opening-check-variant');
            const checkQuantity = document.getElementById('opening-check-quantity');
            const checkAudit = document.getElementById('opening-check-audit');
            const nextStepTitle = document.getElementById('opening-next-step-title');
            const nextStepDescription = document.getElementById('opening-next-step-description');

            function setTone(element, tone) {
                const tones = {
                    success: 'bg-emerald-100 text-emerald-700',
                    warning: 'bg-amber-100 text-amber-800',
                    danger: 'bg-red-100 text-red-700',
                    neutral: 'bg-slate-100 text-slate-700',
                };

                element.className = `rounded-full px-2.5 py-1 text-xs font-medium ${tones[tone] ?? tones.neutral}`;
            }

            function refreshOpeningSummary() {
                const hasVariant = Boolean(variantSelect.value);
                const quantity = Number(quantityInput.value || 0);
                const unitCost = Number(costInput.value || 0);
                costPreview.textContent = `$${unitCost.toFixed(2)}`;
                totalPreview.textContent = `$${(quantity * unitCost).toFixed(2)}`;
                quantityPreview.textContent = quantity.toFixed(3);

                if (hasVariant) {
                    setTone(checkVariant, 'success');
                    checkVariant.textContent = 'Seleccionada';
                } else {
                    setTone(checkVariant, 'neutral');
                    checkVariant.textContent = 'Pendiente';
                }

                if (quantity > 0) {
                    setTone(checkQuantity, 'success');
                    checkQuantity.textContent = 'Lista';
                } else {
                    setTone(checkQuantity, 'neutral');
                    checkQuantity.textContent = 'Pendiente';
                }

                if (auditedInput.checked) {
                    setTone(checkAudit, 'success');
                    checkAudit.textContent = 'Auditado';
                } else {
                    setTone(checkAudit, 'warning');
                    checkAudit.textContent = 'Estimado';
                }

                if (!hasVariant || quantity <= 0) {
                    statusTitle.textContent = 'Completa la variante y la cantidad.';
                    statusDescription.textContent = 'Sin esos datos no hay lote inicial consistente.';
                    nextStepTitle.textContent = 'Completa datos mínimos.';
                    nextStepDescription.textContent = 'Cuando el registro esté consistente, podrás guardarlo y luego revisar el lote generado.';
                } else if (!auditedInput.checked) {
                    statusTitle.textContent = 'El registro quedará como estimado.';
                    statusDescription.textContent = 'Esto ayuda a distinguir stock inicial no auditado de compras reales o conteos confirmados.';
                    nextStepTitle.textContent = 'Decide si corresponde marcarlo como auditado.';
                    nextStepDescription.textContent = 'Si todavía no hubo conteo confiable, conservarlo como estimado es la decisión correcta.';
                } else {
                    statusTitle.textContent = 'El registro está listo para generar lote inicial.';
                    statusDescription.textContent = 'La operación creará entrada de apertura, lote activo y movimiento de inventario.';
                    nextStepTitle.textContent = 'Guarda y luego revisa el lote generado.';
                    nextStepDescription.textContent = 'Después podrás validar el historial de apertura y el lote resultante desde inventario.';
                }
            }

            variantSelect.addEventListener('change', refreshOpeningSummary);
            quantityInput.addEventListener('input', refreshOpeningSummary);
            costInput.addEventListener('input', refreshOpeningSummary);
            auditedInput.addEventListener('change', refreshOpeningSummary);
            refreshOpeningSummary();
        });
    </script>
</x-app-layout>
