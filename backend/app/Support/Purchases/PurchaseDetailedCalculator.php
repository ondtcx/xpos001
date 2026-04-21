<?php

namespace App\Support\Purchases;

use App\Models\PurchaseItem;
use App\Support\Money;

final class PurchaseDetailedCalculator
{
    public function calculate(array $payload): array
    {
        $normalizedItems = array_map(fn (array $item) => $this->normalizeItem($item), $payload['items'] ?? []);

        $lineGrossSubtotal = array_sum(array_column(
            array_filter($normalizedItems, fn (array $item) => $item['line_type'] === PurchaseItem::LINE_TYPE_NORMAL),
            'gross_line_amount'
        ));

        $discountEligibleIndexes = $this->eligibleIndexes($normalizedItems, fn (array $item) => $item['line_type'] === PurchaseItem::LINE_TYPE_NORMAL);
        $ivaEligibleIndexes = $this->eligibleIndexes($normalizedItems, fn (array $item) => $item['eligible_for_global_iva']);
        $iceEligibleIndexes = $this->eligibleIndexes($normalizedItems, fn (array $item) => $item['eligible_for_global_ice']);
        $otherEligibleIndexes = $this->eligibleIndexes($normalizedItems, fn (array $item) => $item['eligible_for_global_other']);
        $extraCostsEligibleIndexes = $discountEligibleIndexes;

        $allocatedGlobalDiscounts = $this->allocateByWeight(
            $normalizedItems,
            $discountEligibleIndexes,
            $this->money($payload['global_discount_amount'] ?? 0),
            'gross_line_amount'
        );
        $allocatedGlobalIva = $this->allocateByWeight(
            $normalizedItems,
            $ivaEligibleIndexes,
            $this->money($payload['global_tax_iva_amount'] ?? 0),
            'gross_line_amount'
        );
        $allocatedGlobalIce = $this->allocateByWeight(
            $normalizedItems,
            $iceEligibleIndexes,
            $this->money($payload['global_tax_ice_amount'] ?? 0),
            'gross_line_amount'
        );
        $allocatedGlobalOther = $this->allocateByWeight(
            $normalizedItems,
            $otherEligibleIndexes,
            $this->money($payload['global_tax_other_amount'] ?? 0),
            'gross_line_amount'
        );
        $allocatedExtraCosts = $this->allocateByWeight(
            $normalizedItems,
            $extraCostsEligibleIndexes,
            $this->money($payload['extra_costs_amount'] ?? 0),
            'gross_line_amount'
        );

        $computedItems = [];

        foreach ($normalizedItems as $index => $item) {
            $finalLineCost = $item['gross_line_amount']
                - $item['line_discount_amount']
                - $allocatedGlobalDiscounts[$index]
                + $item['tax_iva_amount']
                + $item['tax_ice_amount']
                + $item['tax_other_amount']
                + $allocatedGlobalIva[$index]
                + $allocatedGlobalIce[$index]
                + $allocatedGlobalOther[$index]
                + $allocatedExtraCosts[$index];

            $receivedQuantity = $item['line_type'] === PurchaseItem::LINE_TYPE_BONUS
                ? $item['quantity']
                : $item['quantity'] + $item['bonus_quantity'];

            $computedItems[] = array_merge($item, [
                'allocated_global_discount_amount' => $allocatedGlobalDiscounts[$index],
                'allocated_global_tax_iva_amount' => $allocatedGlobalIva[$index],
                'allocated_global_tax_ice_amount' => $allocatedGlobalIce[$index],
                'allocated_global_tax_other_amount' => $allocatedGlobalOther[$index],
                'allocated_extra_costs_amount' => $allocatedExtraCosts[$index],
                'line_subtotal_amount' => $item['gross_line_amount'],
                'received_quantity' => $receivedQuantity,
                'total_cost_amount' => $finalLineCost,
                'unit_cost_final_amount' => $receivedQuantity > 0
                    ? (int) round($finalLineCost / $receivedQuantity)
                    : 0,
            ]);
        }

        return [
            'payment_type' => $payload['payment_type'] ?? 'cash',
            'subtotal_amount' => $lineGrossSubtotal,
            'global_discount_amount' => $this->money($payload['global_discount_amount'] ?? 0),
            'global_tax_iva_amount' => $this->money($payload['global_tax_iva_amount'] ?? 0),
            'global_tax_ice_amount' => $this->money($payload['global_tax_ice_amount'] ?? 0),
            'global_tax_other_amount' => $this->money($payload['global_tax_other_amount'] ?? 0),
            'extra_costs_amount' => $this->money($payload['extra_costs_amount'] ?? 0),
            'total_amount' => array_sum(array_column($computedItems, 'total_cost_amount')),
            'items' => $computedItems,
        ];
    }

    private function normalizeItem(array $item): array
    {
        $lineType = $item['line_type'] ?? PurchaseItem::LINE_TYPE_NORMAL;
        $quantity = $this->quantity($item['quantity'] ?? 0);
        $bonusQuantity = $lineType === PurchaseItem::LINE_TYPE_BONUS ? 0.0 : $this->quantity($item['bonus_quantity'] ?? 0);

        $grossLineAmount = $lineType === PurchaseItem::LINE_TYPE_BONUS
            ? $this->money($item['manual_total_cost'] ?? 0)
            : (int) round($quantity * $this->money($item['unit_cost_base_amount'] ?? $item['unit_cost'] ?? 0));

        return [
            'line_type' => $lineType,
            'variant_id' => $item['variant_id'] ?? null,
            'quantity' => $quantity,
            'bonus_quantity' => $bonusQuantity,
            'unit_cost_base_amount' => $lineType === PurchaseItem::LINE_TYPE_BONUS
                ? 0
                : $this->money($item['unit_cost_base_amount'] ?? $item['unit_cost'] ?? 0),
            'line_discount_amount' => $lineType === PurchaseItem::LINE_TYPE_BONUS ? 0 : $this->money($item['line_discount_amount'] ?? 0),
            'tax_iva_amount' => $this->money($item['tax_iva_amount'] ?? 0),
            'tax_ice_amount' => $this->money($item['tax_ice_amount'] ?? 0),
            'tax_other_amount' => $this->money($item['tax_other_amount'] ?? 0),
            'gross_line_amount' => $grossLineAmount,
            'manual_total_cost_amount' => $lineType === PurchaseItem::LINE_TYPE_BONUS ? $grossLineAmount : 0,
            'eligible_for_global_iva' => $lineType === PurchaseItem::LINE_TYPE_NORMAL
                && ($item['eligible_for_global_iva'] ?? $this->money($item['tax_iva_amount'] ?? 0) > 0),
            'eligible_for_global_ice' => $lineType === PurchaseItem::LINE_TYPE_NORMAL
                && ($item['eligible_for_global_ice'] ?? $this->money($item['tax_ice_amount'] ?? 0) > 0),
            'eligible_for_global_other' => $lineType === PurchaseItem::LINE_TYPE_NORMAL
                && ($item['eligible_for_global_other'] ?? $this->money($item['tax_other_amount'] ?? 0) > 0),
            'expiration_date' => $item['expiration_date'] ?? null,
            'notes' => $item['notes'] ?? null,
        ];
    }

    private function eligibleIndexes(array $items, callable $resolver): array
    {
        $indexes = [];

        foreach ($items as $index => $item) {
            if ($resolver($item)) {
                $indexes[] = $index;
            }
        }

        return $indexes;
    }

    private function allocateByWeight(array $items, array $indexes, int $amount, string $weightKey): array
    {
        $allocations = array_fill(0, count($items), 0);

        if ($amount === 0 || $indexes === []) {
            return $allocations;
        }

        $weightSum = array_sum(array_map(fn (int $index) => $items[$index][$weightKey], $indexes));

        if ($weightSum <= 0) {
            return $allocations;
        }

        $remainders = [];
        $assigned = 0;

        foreach ($indexes as $index) {
            $raw = ($amount * $items[$index][$weightKey]) / $weightSum;
            $allocated = (int) floor($raw);
            $allocations[$index] = $allocated;
            $assigned += $allocated;
            $remainders[$index] = $raw - $allocated;
        }

        $residual = $amount - $assigned;

        if ($residual > 0) {
            arsort($remainders);

            foreach (array_keys($remainders) as $index) {
                if ($residual === 0) {
                    break;
                }

                $allocations[$index]++;
                $residual--;
            }
        }

        return $allocations;
    }

    private function money(string|float|int|null $amount): int
    {
        return Money::dollarsToCents($amount ?? 0);
    }

    private function quantity(string|float|int|null $quantity): float
    {
        return round((float) ($quantity ?? 0), 3);
    }
}
