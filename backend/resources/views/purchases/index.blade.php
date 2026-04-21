@php use App\Support\Money; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800">Compras</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('purchases.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Compra rápida</a>
                <a href="{{ route('purchases.detailed.create') }}" class="rounded-md border border-indigo-200 px-4 py-2 text-sm font-medium text-indigo-700">Compra detallada</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            @if ($errors->has('purchase'))
                <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('purchase') }}</div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Fecha</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Modo</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Proveedor</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Factura</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Pago</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Estado</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Total</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Usuario</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($purchases as $purchase)
                            <tr>
                                <td class="px-4 py-3 text-gray-700">{{ optional($purchase->purchased_at)->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3">
                                    @if ($purchase->isDetailed())
                                        <span class="rounded-full bg-indigo-100 px-2 py-1 text-xs font-medium text-indigo-700">Detallada</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">Rápida</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-900">{{ $purchase->supplier?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $purchase->invoice_number ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $purchase->payment_type }}</td>
                                <td class="px-4 py-3">
                                    @if ($purchase->isVoided())
                                        <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Anulada</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Confirmada</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-900">{{ Money::format($purchase->total_amount) }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $purchase->creator?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($purchase->isConfirmed())
                                        <div class="flex flex-col items-start gap-2">
                                            @if ($purchase->can_edit_detailed)
                                                <a href="{{ route('purchases.detailed.edit', $purchase) }}" class="text-indigo-700 hover:text-indigo-900">Editar</a>
                                            @elseif ($purchase->isDetailed())
                                                <span class="text-xs text-gray-400">Edición bloqueada por consumo</span>
                                            @else
                                                <span class="text-xs text-gray-400">Compra rápida sin edición detallada</span>
                                            @endif

                                            @if ($purchase->can_void_purchase)
                                                <form method="POST" action="{{ route('purchases.void', $purchase) }}" data-purchase-id="{{ $purchase->id }}" data-purchase-label="{{ $purchase->invoice_number ?: 'Compra #' . $purchase->id }}" class="void-purchase-form">
                                                    @csrf
                                                    <input type="hidden" name="void_reason" value="">
                                                    <button type="button" class="open-void-modal text-red-600 hover:text-red-800">Anular</button>
                                                </form>
                                            @else
                                                <span class="text-xs text-gray-400">Anulación bloqueada por consumo</span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="text-xs text-gray-500">
                                            <p>{{ $purchase->void_reason }}</p>
                                            <p class="mt-1">por {{ $purchase->voider?->name ?? '—' }}</p>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-6 text-center text-gray-500">Aún no hay compras registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="void-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/40 px-4">
        <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Anular compra</h3>
                    <p id="void-modal-description" class="mt-1 text-sm text-gray-600">Debes registrar un motivo antes de anular.</p>
                </div>
                <button type="button" id="close-void-modal" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            <div class="mt-4 space-y-2">
                <label for="void-reason-input" class="block text-sm font-medium text-gray-700">Motivo de anulación</label>
                <textarea id="void-reason-input" rows="4" class="block w-full rounded-md border-gray-300 shadow-sm" placeholder="Explica por qué esta compra debe anularse."></textarea>
                <p id="void-modal-error" class="hidden text-sm text-red-600">El motivo es obligatorio.</p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" id="cancel-void-modal" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">Cancelar</button>
                <button type="button" id="submit-void-modal" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white">Confirmar anulación</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('void-modal');
            const description = document.getElementById('void-modal-description');
            const reasonInput = document.getElementById('void-reason-input');
            const errorMessage = document.getElementById('void-modal-error');
            const openButtons = document.querySelectorAll('.open-void-modal');
            const closeButton = document.getElementById('close-void-modal');
            const cancelButton = document.getElementById('cancel-void-modal');
            const submitButton = document.getElementById('submit-void-modal');
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
                    description.textContent = `Vas a anular ${activeForm.dataset.purchaseLabel}. Debes registrar un motivo antes de continuar.`;
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
