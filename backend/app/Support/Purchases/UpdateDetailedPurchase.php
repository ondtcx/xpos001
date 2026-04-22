<?php

namespace App\Support\Purchases;

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

final class UpdateDetailedPurchase
{
    public function __construct(
        private readonly PurchaseDetailedCalculator $calculator,
        private readonly PurchaseCorrectionService $correctionService,
        private readonly DetailedPurchasePersister $persister,
    ) {
    }

    public function handle(Purchase $purchase, array $validated, int $userId): Purchase
    {
        $this->correctionService->assertEditable($purchase);
        $computed = $this->calculator->calculate($validated);

        return DB::transaction(function () use ($purchase, $validated, $computed) {
            $this->persister->clearPurchaseArtifacts($purchase);

            $purchase->update([
                'supplier_id' => $validated['supplier_id'] ?? null,
                'invoice_number' => $validated['invoice_number'] ?? null,
                'purchased_at' => $validated['purchased_at'],
                'payment_type' => $computed['payment_type'],
                'entry_mode' => Purchase::ENTRY_MODE_DETAILED,
                'is_credit' => $computed['payment_type'] === 'credit',
                'subtotal_amount' => $computed['subtotal_amount'],
                'global_discount_amount' => $computed['global_discount_amount'],
                'global_tax_amount' => $computed['global_tax_iva_amount'] + $computed['global_tax_ice_amount'] + $computed['global_tax_other_amount'],
                'global_tax_iva_amount' => $computed['global_tax_iva_amount'],
                'global_tax_ice_amount' => $computed['global_tax_ice_amount'],
                'global_tax_other_amount' => $computed['global_tax_other_amount'],
                'extra_costs_amount' => $computed['extra_costs_amount'],
                'total_amount' => $computed['total_amount'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->persister->persistComputedItems($purchase, $validated, $computed, $purchase->created_by);

            return $purchase->fresh(['supplier', 'creator']);
        });
    }
}
