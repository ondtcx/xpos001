<?php

namespace App\Support\Purchases;

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

final class CreateDetailedPurchase
{
    public function __construct(
        private readonly PurchaseDetailedCalculator $calculator,
        private readonly DetailedPurchasePersister $persister,
    ) {
    }

    public function handle(array $validated, int $userId): Purchase
    {
        $computed = $this->calculator->calculate($validated);

        return DB::transaction(function () use ($validated, $computed, $userId) {
            $purchase = Purchase::query()->create([
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
                'created_by' => $userId,
            ]);

            $this->persister->persistComputedItems($purchase, $validated, $computed, $userId);

            return $purchase;
        });
    }
}
