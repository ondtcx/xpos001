<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Registrar inventario inicial</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('opening-inventory.store') }}" class="space-y-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @csrf

                <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                    Usa este formulario para regularizar stock por tandas. Si todavía no auditaste el conteo, deja el registro sin marcar como auditado para distinguirlo de compras reales.
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Variante</label>
                    <select name="variant_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        <option value="">Selecciona una variante</option>
                        @foreach ($variants as $variant)
                            <option value="{{ $variant->id }}" @selected((string) old('variant_id') === (string) $variant->id)>{{ $variant->product->name }} — {{ $variant->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cantidad</label>
                        <input name="quantity" type="number" step="0.001" min="0.001" value="{{ old('quantity') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Costo estimado unitario</label>
                        <input name="estimated_unit_cost" type="number" step="0.01" min="0" value="{{ old('estimated_unit_cost') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha</label>
                        <input name="recorded_at" type="datetime-local" value="{{ old('recorded_at', now()->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>

                <div class="grid gap-4 rounded-lg bg-gray-50 p-4 md:grid-cols-2 text-sm">
                    <div>
                        <p class="text-gray-500">Costo unitario estimado</p>
                        <p id="opening-cost-preview" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Valor total estimado</p>
                        <p id="opening-total-preview" class="mt-1 text-lg font-semibold text-gray-900">$0.00</p>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_audited" value="1" @checked(old('is_audited'))>
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
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const quantityInput = document.querySelector('input[name="quantity"]');
            const costInput = document.querySelector('input[name="estimated_unit_cost"]');
            const costPreview = document.getElementById('opening-cost-preview');
            const totalPreview = document.getElementById('opening-total-preview');

            function refreshOpeningSummary() {
                const quantity = Number(quantityInput.value || 0);
                const unitCost = Number(costInput.value || 0);
                costPreview.textContent = `$${unitCost.toFixed(2)}`;
                totalPreview.textContent = `$${(quantity * unitCost).toFixed(2)}`;
            }

            quantityInput.addEventListener('input', refreshOpeningSummary);
            costInput.addEventListener('input', refreshOpeningSummary);
            refreshOpeningSummary();
        });
    </script>
</x-app-layout>
