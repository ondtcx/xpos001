<?php

namespace App\Support\Sales;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\InventoryMovement;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class VoidSaleService
{
    public function validate(Sale $sale): void
    {
        if (! $sale->canBeVoided()) {
            throw ValidationException::withMessages([
                'sale' => 'Solo se pueden anular ventas confirmadas.',
            ]);
        }

        $hasCashImpacts = $sale->payments()->where('is_reversed', false)->exists()
            || $sale->receivable()->whereHas('payments', fn ($query) => $query->where('is_reversed', false))->exists();

        if ($hasCashImpacts) {
            $currentCashSession = CashSession::query()->where('status', 'open')->latest('opened_at')->first();

            if ($currentCashSession === null) {
                throw ValidationException::withMessages([
                    'sale' => 'Necesitas una caja abierta actual para anular esta venta porque tiene movimientos monetarios que deben revertirse.',
                ]);
            }
        }
    }

    public function handle(Sale $sale, string $reason, int $userId): Sale
    {
        $this->validate($sale);

        $sale->load([
            'items.lotConsumptions.lot',
            'payments',
            'receivable.payments',
        ]);

        $currentCashSession = CashSession::query()->where('status', 'open')->latest('opened_at')->first();
        $voidedAt = now();

        return DB::transaction(function () use ($sale, $reason, $userId, $currentCashSession, $voidedAt) {
            foreach ($sale->items as $item) {
                foreach ($item->lotConsumptions as $consumption) {
                    $lot = $consumption->lot;

                    if ($lot !== null) {
                        $lot->update([
                            'available_quantity' => round((float) $lot->available_quantity + (float) $consumption->quantity, 3),
                            'status' => 'active',
                        ]);

                        InventoryMovement::query()->create([
                            'variant_id' => $lot->variant_id,
                            'lot_id' => $lot->id,
                            'movement_type' => 'sale_void_reversal',
                            'quantity' => $consumption->quantity,
                            'unit_cost_amount' => $consumption->unit_cost_amount,
                            'reference_type' => 'sale',
                            'reference_id' => $sale->id,
                            'movement_at' => $voidedAt,
                            'notes' => 'Reversa de anulación total de venta',
                            'created_by' => $userId,
                        ]);
                    }
                }
            }

            foreach ($sale->payments as $payment) {
                if ($payment->isReversed()) {
                    continue;
                }

                $payment->update([
                    'is_reversed' => true,
                    'reversed_at' => $voidedAt,
                    'reversed_by' => $userId,
                    'reversal_reason' => $reason,
                ]);

                if ($currentCashSession !== null) {
                    CashMovement::query()->create([
                        'cash_session_id' => $currentCashSession->id,
                        'movement_type' => 'sale_payment_reversal',
                        'amount' => -$payment->amount,
                        'payment_method' => $payment->payment_method,
                        'reference_type' => 'sale',
                        'reference_id' => $sale->id,
                        'notes' => 'Reversa de pago por anulación total de venta',
                        'created_by' => $userId,
                        'created_at' => $voidedAt,
                    ]);
                }
            }

            /** @var Receivable|null $receivable */
            $receivable = $sale->receivable->first();

            if ($receivable !== null) {
                foreach ($receivable->payments as $payment) {
                    if ($payment->isReversed()) {
                        continue;
                    }

                    $payment->update([
                        'is_reversed' => true,
                        'reversed_at' => $voidedAt,
                        'reversed_by' => $userId,
                        'reversal_reason' => $reason,
                    ]);

                    if ($currentCashSession !== null) {
                        CashMovement::query()->create([
                            'cash_session_id' => $currentCashSession->id,
                            'movement_type' => 'receivable_payment_reversal',
                            'amount' => -$payment->amount,
                            'payment_method' => $payment->payment_method,
                            'reference_type' => 'receivable',
                            'reference_id' => $receivable->id,
                            'notes' => 'Reversa de abono por anulación total de venta',
                            'created_by' => $userId,
                            'created_at' => $voidedAt,
                        ]);
                    }
                }

                $receivable->update([
                    'pending_amount' => 0,
                    'status' => 'cancelled',
                    'cancelled_at' => $voidedAt,
                    'cancelled_by' => $userId,
                    'cancel_reason' => $reason,
                ]);
            }

            $sale->update([
                'status' => Sale::STATUS_VOIDED,
                'voided_at' => $voidedAt,
                'voided_by' => $userId,
                'void_reason' => $reason,
            ]);

            return $sale->fresh(['customer', 'creator', 'voider', 'payments', 'items']);
        });
    }
}
