<?php

namespace App\Support\Purchases;

use App\Models\InventoryMovement;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PurchaseCorrectionService
{
    public function hasConsumedLots(Purchase $purchase): bool
    {
        $lotIds = $purchase->lots()->pluck('inventory_lots.id');

        if ($lotIds->isEmpty()) {
            return false;
        }

        return InventoryMovement::query()
            ->whereIn('lot_id', $lotIds)
            ->where('quantity', '<', 0)
            ->exists();
    }

    public function assertEditable(Purchase $purchase): void
    {
        if (! $purchase->isConfirmed()) {
            throw ValidationException::withMessages([
                'purchase' => 'Solo se pueden editar compras confirmadas.',
            ]);
        }

        if ($this->hasConsumedLots($purchase)) {
            throw ValidationException::withMessages([
                'purchase' => 'No puedes editar esta compra porque alguno de sus lotes ya fue consumido. Corrige con ajustes manuales.',
            ]);
        }
    }

    public function assertVoidable(Purchase $purchase): void
    {
        $this->assertEditable($purchase);
    }

    public function void(Purchase $purchase, string $reason, int $userId): void
    {
        $this->assertVoidable($purchase);

        DB::transaction(function () use ($purchase, $reason, $userId) {
            $lotIds = $purchase->lots()->pluck('inventory_lots.id');

            InventoryMovement::query()
                ->where('reference_type', 'purchase')
                ->where('reference_id', $purchase->id)
                ->delete();

            if ($lotIds->isNotEmpty()) {
                InventoryMovement::query()->whereIn('lot_id', $lotIds)->delete();
                $purchase->lots()->delete();
            }

            $purchase->items()->delete();

            $purchase->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => $userId,
                'void_reason' => $reason,
            ]);
        });
    }
}
