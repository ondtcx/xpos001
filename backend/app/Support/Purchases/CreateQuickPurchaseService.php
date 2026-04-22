<?php

namespace App\Support\Purchases;

use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\SupplierVariantRef;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

final class CreateQuickPurchaseService
{
    public function handle(array $validated, int $userId): Purchase
    {
        return DB::transaction(function () use ($validated, $userId) {
            $subtotal = 0;

            $purchase = Purchase::query()->create([
                'supplier_id' => $validated['supplier_id'] ?? null,
                'invoice_number' => $validated['invoice_number'] ?? null,
                'purchased_at' => $validated['purchased_at'],
                'payment_type' => $validated['payment_type'],
                'entry_mode' => Purchase::ENTRY_MODE_QUICK,
                'is_credit' => (bool) ($validated['is_credit'] ?? false),
                'subtotal_amount' => 0,
                'global_discount_amount' => 0,
                'global_tax_amount' => 0,
                'extra_costs_amount' => 0,
                'total_amount' => 0,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($validated['items'] as $item) {
                $unitCostAmount = Money::dollarsToCents($item['unit_cost']);
                $lineSubtotal = (int) round(((float) $item['quantity']) * $unitCostAmount);
                $subtotal += $lineSubtotal;

                $purchaseItem = PurchaseItem::query()->create([
                    'purchase_id' => $purchase->id,
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost_base_amount' => $unitCostAmount,
                    'line_subtotal_amount' => $lineSubtotal,
                    'line_discount_amount' => 0,
                    'tax_vat_amount' => 0,
                    'tax_fixed_amount' => 0,
                    'tax_other_amount' => 0,
                    'gift_quantity' => 0,
                    'total_cost_amount' => $lineSubtotal,
                    'expiration_date' => $item['expiration_date'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);

                $lot = InventoryLot::query()->create([
                    'variant_id' => $item['variant_id'],
                    'purchase_item_id' => $purchaseItem->id,
                    'origin_type' => 'purchase',
                    'origin_id' => $purchase->id,
                    'received_at' => $validated['purchased_at'],
                    'expiration_date' => $item['expiration_date'] ?? null,
                    'initial_quantity' => $item['quantity'],
                    'available_quantity' => $item['quantity'],
                    'bonus_quantity' => 0,
                    'unit_cost_final_amount' => $unitCostAmount,
                    'suggested_sale_price_amount' => null,
                    'is_estimated' => false,
                    'status' => 'active',
                ]);

                InventoryMovement::query()->create([
                    'variant_id' => $item['variant_id'],
                    'lot_id' => $lot->id,
                    'movement_type' => 'purchase_entry',
                    'quantity' => $item['quantity'],
                    'unit_cost_amount' => $unitCostAmount,
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'movement_at' => $validated['purchased_at'],
                    'notes' => $item['notes'] ?? null,
                    'created_by' => $userId,
                ]);

                if (! empty($validated['supplier_id'])) {
                    SupplierVariantRef::query()->updateOrCreate(
                        [
                            'supplier_id' => $validated['supplier_id'],
                            'variant_id' => $item['variant_id'],
                        ],
                        [
                            'last_purchase_price_amount' => $unitCostAmount,
                            'last_purchase_at' => $validated['purchased_at'],
                        ],
                    );
                }
            }

            $purchase->update([
                'subtotal_amount' => $subtotal,
                'total_amount' => $subtotal,
            ]);

            return $purchase;
        });
    }
}
