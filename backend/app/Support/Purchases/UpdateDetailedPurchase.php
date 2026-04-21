<?php

namespace App\Support\Purchases;

use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\SupplierVariantRef;
use Illuminate\Support\Facades\DB;

final class UpdateDetailedPurchase
{
    public function __construct(
        private readonly PurchaseDetailedCalculator $calculator,
        private readonly PurchaseCorrectionService $correctionService,
    ) {
    }

    public function handle(Purchase $purchase, array $validated, int $userId): Purchase
    {
        $this->correctionService->assertEditable($purchase);
        $computed = $this->calculator->calculate($validated);

        return DB::transaction(function () use ($purchase, $validated, $computed) {
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

            foreach ($computed['items'] as $item) {
                $purchaseItem = PurchaseItem::query()->create([
                    'purchase_id' => $purchase->id,
                    'variant_id' => $item['variant_id'],
                    'line_type' => $item['line_type'],
                    'quantity' => $item['quantity'],
                    'bonus_quantity' => $item['bonus_quantity'],
                    'unit_cost_base_amount' => $item['unit_cost_base_amount'],
                    'line_subtotal_amount' => $item['line_subtotal_amount'],
                    'line_discount_amount' => $item['line_discount_amount'],
                    'tax_iva_amount' => $item['tax_iva_amount'],
                    'tax_ice_amount' => $item['tax_ice_amount'],
                    'tax_vat_amount' => $item['tax_iva_amount'],
                    'tax_fixed_amount' => $item['tax_ice_amount'],
                    'tax_other_amount' => $item['tax_other_amount'],
                    'allocated_global_discount_amount' => $item['allocated_global_discount_amount'],
                    'allocated_global_tax_iva_amount' => $item['allocated_global_tax_iva_amount'],
                    'allocated_global_tax_ice_amount' => $item['allocated_global_tax_ice_amount'],
                    'allocated_global_tax_other_amount' => $item['allocated_global_tax_other_amount'],
                    'allocated_extra_costs_amount' => $item['allocated_extra_costs_amount'],
                    'gift_quantity' => $item['bonus_quantity'],
                    'unit_cost_final_amount' => $item['unit_cost_final_amount'],
                    'total_cost_amount' => $item['total_cost_amount'],
                    'expiration_date' => $item['expiration_date'],
                    'notes' => $item['notes'],
                ]);

                $lot = InventoryLot::query()->create([
                    'variant_id' => $item['variant_id'],
                    'purchase_item_id' => $purchaseItem->id,
                    'origin_type' => 'purchase',
                    'origin_id' => $purchase->id,
                    'received_at' => $validated['purchased_at'],
                    'expiration_date' => $item['expiration_date'],
                    'initial_quantity' => $item['received_quantity'],
                    'available_quantity' => $item['received_quantity'],
                    'bonus_quantity' => $item['bonus_quantity'],
                    'unit_cost_final_amount' => $item['unit_cost_final_amount'],
                    'suggested_sale_price_amount' => null,
                    'is_estimated' => false,
                    'status' => 'active',
                ]);

                InventoryMovement::query()->create([
                    'variant_id' => $item['variant_id'],
                    'lot_id' => $lot->id,
                    'movement_type' => 'purchase_entry',
                    'quantity' => $item['received_quantity'],
                    'unit_cost_amount' => $item['unit_cost_final_amount'],
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'movement_at' => $validated['purchased_at'],
                    'notes' => $item['notes'],
                    'created_by' => $purchase->created_by,
                ]);

                if (! empty($validated['supplier_id']) && $item['unit_cost_final_amount'] > 0) {
                    SupplierVariantRef::query()->updateOrCreate(
                        [
                            'supplier_id' => $validated['supplier_id'],
                            'variant_id' => $item['variant_id'],
                        ],
                        [
                            'last_purchase_price_amount' => $item['unit_cost_final_amount'],
                            'last_purchase_at' => $validated['purchased_at'],
                        ],
                    );
                }
            }

            return $purchase->fresh(['supplier', 'creator']);
        });
    }
}
