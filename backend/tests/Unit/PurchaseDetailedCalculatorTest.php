<?php

namespace Tests\Unit;

use App\Models\PurchaseItem;
use App\Support\Purchases\PurchaseDetailedCalculator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseDetailedCalculatorTest extends TestCase
{
    #[Test]
    public function it_calculates_prorated_amounts_for_normal_lines(): void
    {
        $calculator = new PurchaseDetailedCalculator();

        $result = $calculator->calculate([
            'payment_type' => 'cash',
            'global_discount_amount' => '3.00',
            'global_tax_iva_amount' => '6.00',
            'global_tax_ice_amount' => '0.00',
            'global_tax_other_amount' => '0.00',
            'extra_costs_amount' => '2.00',
            'items' => [
                [
                    'line_type' => PurchaseItem::LINE_TYPE_NORMAL,
                    'variant_id' => 1,
                    'quantity' => 10,
                    'bonus_quantity' => 2,
                    'unit_cost' => '2.00',
                    'line_discount_amount' => '1.00',
                    'tax_iva_amount' => '1.20',
                    'tax_ice_amount' => '0.00',
                    'tax_other_amount' => '0.00',
                    'eligible_for_global_iva' => true,
                    'eligible_for_global_ice' => false,
                    'eligible_for_global_other' => false,
                ],
                [
                    'line_type' => PurchaseItem::LINE_TYPE_NORMAL,
                    'variant_id' => 2,
                    'quantity' => 5,
                    'bonus_quantity' => 0,
                    'unit_cost' => '1.00',
                    'line_discount_amount' => '0.00',
                    'tax_iva_amount' => '0.00',
                    'tax_ice_amount' => '0.00',
                    'tax_other_amount' => '0.00',
                    'eligible_for_global_iva' => true,
                    'eligible_for_global_ice' => false,
                    'eligible_for_global_other' => false,
                ],
            ],
        ]);

        $this->assertSame(2500, $result['subtotal_amount']);
        $this->assertSame(3020, $result['total_amount']);

        $first = $result['items'][0];
        $second = $result['items'][1];

        $this->assertSame(2000, $first['line_subtotal_amount']);
        $this->assertSame(240, $first['allocated_global_discount_amount']);
        $this->assertSame(480, $first['allocated_global_tax_iva_amount']);
        $this->assertSame(160, $first['allocated_extra_costs_amount']);
        $this->assertSame(12.0, $first['received_quantity']);
        $this->assertSame(2420, $first['total_cost_amount']);
        $this->assertSame(202, $first['unit_cost_final_amount']);

        $this->assertSame(500, $second['line_subtotal_amount']);
        $this->assertSame(60, $second['allocated_global_discount_amount']);
        $this->assertSame(120, $second['allocated_global_tax_iva_amount']);
        $this->assertSame(40, $second['allocated_extra_costs_amount']);
        $this->assertSame(5.0, $second['received_quantity']);
        $this->assertSame(600, $second['total_cost_amount']);
        $this->assertSame(120, $second['unit_cost_final_amount']);
    }

    #[Test]
    public function it_handles_bonus_lines_with_manual_total_cost(): void
    {
        $calculator = new PurchaseDetailedCalculator();

        $result = $calculator->calculate([
            'payment_type' => 'credit',
            'global_discount_amount' => '0',
            'global_tax_iva_amount' => '0',
            'global_tax_ice_amount' => '0',
            'global_tax_other_amount' => '0',
            'extra_costs_amount' => '0',
            'items' => [
                [
                    'line_type' => PurchaseItem::LINE_TYPE_BONUS,
                    'variant_id' => 3,
                    'quantity' => 4,
                    'manual_total_cost' => '1.50',
                    'tax_iva_amount' => '0',
                    'tax_ice_amount' => '0',
                    'tax_other_amount' => '0',
                ],
            ],
        ]);

        $item = $result['items'][0];

        $this->assertSame('credit', $result['payment_type']);
        $this->assertSame(150, $result['total_amount']);
        $this->assertSame(PurchaseItem::LINE_TYPE_BONUS, $item['line_type']);
        $this->assertSame(150, $item['total_cost_amount']);
        $this->assertSame(38, $item['unit_cost_final_amount']);
        $this->assertSame(4.0, $item['received_quantity']);
    }
}
