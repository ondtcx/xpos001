@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Ventas</h2>
                <p class="text-sm text-gray-500">Consulta ventas recientes y su relación con pagos y fiado.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('receivables.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Ver fiados</a>
                <a href="{{ route('sales.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Nueva venta</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            @if ($errors->has('sale'))
                <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('sale') }}</div>
            @endif

            <div class="mb-4 grid gap-4 md:grid-cols-3">
                <a href="{{ route('reports.index') }}" class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300">
                    <p class="text-sm text-gray-500">Reportes</p>
                    <p class="mt-1 font-semibold text-gray-900">Ver utilidad y ventas del período</p>
                </a>
                <a href="{{ route('cash.index') }}" class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300">
                    <p class="text-sm text-gray-500">Caja</p>
                    <p class="mt-1 font-semibold text-gray-900">Revisar pagos vinculados y cierres</p>
                </a>
                <a href="{{ route('customers.index') }}" class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300">
                    <p class="text-sm text-gray-500">Clientes</p>
                    <p class="mt-1 font-semibold text-gray-900">Gestionar clientes frecuentes y fiados</p>
                </a>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Fecha</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Cliente</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Total</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Pagado</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Fiado</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Usuario</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Señales</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($sales as $sale)
                            <tr>
                                <td class="px-4 py-3 text-gray-700">{{ optional($sale->sold_at)->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-gray-900">{{ $sale->customer?->name ?? 'Venta anónima' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ Money::format($sale->total_amount) }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ Money::format($sale->paid_amount) }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ Money::format($sale->credit_amount) }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $sale->creator?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    @if ($sale->isVoided())
                                        <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700">Anulada</span>
                                    @elseif ($sale->credit_amount > 0)
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">Con saldo pendiente</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">Cobrada</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    <div class="flex flex-wrap gap-2">
                                        @if ($sale->items->contains(fn ($item) => $item->has_manual_price_override))
                                            <span class="rounded-full bg-indigo-100 px-2 py-1 font-medium text-indigo-700">Override precio</span>
                                        @endif
                                        @if ($sale->items->contains(fn ($item) => $item->has_stock_warning))
                                            <span class="rounded-full bg-amber-100 px-2 py-1 font-medium text-amber-800">Stock insuficiente</span>
                                        @endif
                                        @if ($sale->items->contains(fn ($item) => $item->has_cost_warning))
                                            <span class="rounded-full bg-orange-100 px-2 py-1 font-medium text-orange-800">Costo pendiente</span>
                                        @endif
                                        @if (! $sale->items->contains(fn ($item) => $item->has_manual_price_override || $item->has_stock_warning || $item->has_cost_warning))
                                            <span class="text-gray-400">Sin novedades</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="mb-2">
                                        <a href="{{ route('sales.show', $sale) }}" class="text-indigo-700 hover:text-indigo-900">Ver detalle</a>
                                    </div>

                                    @if ($sale->isVoided())
                                        <div class="text-xs text-gray-500">
                                            <p>{{ $sale->void_reason }}</p>
                                            <p class="mt-1">por {{ $sale->voider?->name ?? '—' }}</p>
                                        </div>
                                    @elseif ($sale->can_void_sale)
                                        <form method="POST" action="{{ route('sales.void', $sale) }}" data-sale-label="Venta #{{ $sale->id }}" class="void-sale-form">
                                            @csrf
                                            <input type="hidden" name="void_reason" value="">
                                            <button type="button" class="open-sale-void-modal text-red-600 hover:text-red-800">Anular</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">Anulación no disponible</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-6 text-center text-gray-500">Aún no hay ventas registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="sale-void-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/40 px-4">
        <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Anular venta</h3>
                    <p id="sale-void-description" class="mt-1 text-sm text-gray-600">Debes registrar un motivo antes de continuar.</p>
                </div>
                <button type="button" id="close-sale-void-modal" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            <div class="mt-4 space-y-2">
                <label for="sale-void-reason-input" class="block text-sm font-medium text-gray-700">Motivo de anulación</label>
                <textarea id="sale-void-reason-input" rows="4" class="block w-full rounded-md border-gray-300 shadow-sm" placeholder="Explica por qué esta venta debe anularse."></textarea>
                <p id="sale-void-modal-error" class="hidden text-sm text-red-600">El motivo es obligatorio.</p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" id="cancel-sale-void-modal" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Cancelar</button>
                <button type="button" id="submit-sale-void-modal" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white">Confirmar anulación</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('sale-void-modal');
            const description = document.getElementById('sale-void-description');
            const reasonInput = document.getElementById('sale-void-reason-input');
            const errorMessage = document.getElementById('sale-void-modal-error');
            const openButtons = document.querySelectorAll('.open-sale-void-modal');
            const closeButton = document.getElementById('close-sale-void-modal');
            const cancelButton = document.getElementById('cancel-sale-void-modal');
            const submitButton = document.getElementById('submit-sale-void-modal');
            let activeForm = null;

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                reasonInput.value = '';
                errorMessage.classList.add('hidden');
                activeForm = null;
            }

            openButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    activeForm = button.closest('form');
                    description.textContent = `Vas a anular ${activeForm.dataset.saleLabel}. Debes registrar un motivo antes de continuar.`;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    reasonInput.focus();
                });
            });

            [closeButton, cancelButton].forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            submitButton.addEventListener('click', () => {
                const reason = reasonInput.value.trim();

                if (!activeForm || !reason) {
                    errorMessage.classList.remove('hidden');
                    return;
                }

                activeForm.querySelector('input[name="void_reason"]').value = reason;
                activeForm.submit();
            });
        });
    </script>
</x-app-layout>
