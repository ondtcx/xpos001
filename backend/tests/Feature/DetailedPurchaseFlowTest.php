<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DetailedPurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_detailed_purchase_with_lots_and_movements(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::query()->create(['name' => 'Proveedor Uno', 'is_active' => true]);
        [$variantA, $variantB] = $this->createVariants();

        $response = $this->post(route('purchases.detailed.store'), [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'FAC-001',
            'purchased_at' => '2026-04-21 10:00:00',
            'payment_type' => 'cash',
            'global_discount_amount' => '3.00',
            'global_tax_iva_amount' => '6.00',
            'global_tax_ice_amount' => '0',
            'global_tax_other_amount' => '0',
            'extra_costs_amount' => '2.00',
            'notes' => 'Compra detallada de prueba',
            'items' => [
                [
                    'line_type' => 'normal',
                    'variant_id' => $variantA->id,
                    'quantity' => 10,
                    'bonus_quantity' => 2,
                    'unit_cost' => '2.00',
                    'line_discount_amount' => '1.00',
                    'tax_iva_amount' => '1.20',
                    'tax_ice_amount' => '0',
                    'tax_other_amount' => '0',
                    'eligible_for_global_iva' => '1',
                ],
                [
                    'line_type' => 'bonus',
                    'variant_id' => $variantB->id,
                    'quantity' => 4,
                    'manual_total_cost' => '1.50',
                    'tax_iva_amount' => '0',
                    'tax_ice_amount' => '0',
                    'tax_other_amount' => '0',
                ],
            ],
        ]);

        $response->assertRedirect(route('purchases.index'));
        $response->assertSessionHasNoErrors();

        $purchase = Purchase::query()->firstOrFail();

        $this->assertSame('confirmed', $purchase->status);
        $this->assertSame(2000, $purchase->subtotal_amount);
        $this->assertSame(2670, $purchase->total_amount);
        $this->assertCount(2, $purchase->items);
        $this->assertCount(2, $purchase->lots);

        $normalLot = InventoryLot::query()->where('variant_id', $variantA->id)->firstOrFail();
        $bonusLot = InventoryLot::query()->where('variant_id', $variantB->id)->firstOrFail();

        $this->assertSame('12.000', $normalLot->initial_quantity);
        $this->assertSame(2, (int) $normalLot->bonus_quantity);
        $this->assertSame('4.000', $bonusLot->initial_quantity);
        $this->assertSame(38, $bonusLot->unit_cost_final_amount);
        $this->assertDatabaseCount('inventory_movements', 2);
    }

    #[Test]
    public function it_voids_a_purchase_when_no_lot_has_been_consumed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $purchase = $this->createDetailedPurchase($user);

        $response = $this->post(route('purchases.void', $purchase), [
            'void_reason' => 'Factura mal digitada',
        ]);

        $response->assertRedirect(route('purchases.index'));
        $response->assertSessionHasNoErrors();

        $purchase->refresh();

        $this->assertTrue($purchase->isVoided());
        $this->assertSame('Factura mal digitada', $purchase->void_reason);
        $this->assertNotNull($purchase->voided_at);
        $this->assertSame($user->id, $purchase->voided_by);
        $this->assertDatabaseCount('purchase_items', 0);
        $this->assertDatabaseCount('inventory_lots', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    #[Test]
    public function it_blocks_voiding_when_a_purchase_lot_has_been_consumed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $purchase = $this->createDetailedPurchase($user);
        $lot = $purchase->lots()->firstOrFail();

        InventoryMovement::query()->create([
            'variant_id' => $lot->variant_id,
            'lot_id' => $lot->id,
            'movement_type' => 'sale_exit',
            'quantity' => -1,
            'unit_cost_amount' => $lot->unit_cost_final_amount,
            'reference_type' => 'sale',
            'reference_id' => 999,
            'movement_at' => now(),
            'notes' => 'Consumo de prueba',
            'created_by' => $user->id,
        ]);

        $response = $this->from(route('purchases.index'))->post(route('purchases.void', $purchase), [
            'void_reason' => 'Intento inválido',
        ]);

        $response->assertRedirect(route('purchases.index'));
        $response->assertSessionHasErrors('purchase');

        $purchase->refresh();

        $this->assertTrue($purchase->isConfirmed());
        $this->assertNull($purchase->voided_at);
        $this->assertDatabaseCount('purchase_items', 1);
    }

    #[Test]
    public function it_updates_a_detailed_purchase_when_no_lot_has_been_consumed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $purchase = $this->createDetailedPurchase($user);
        [$variant] = $this->createVariants();

        $response = $this->patch(route('purchases.detailed.update', $purchase), [
            'supplier_id' => $purchase->supplier_id,
            'invoice_number' => $purchase->invoice_number,
            'purchased_at' => '2026-04-22 08:00:00',
            'payment_type' => 'transfer',
            'global_discount_amount' => '1.00',
            'global_tax_iva_amount' => '0.50',
            'global_tax_ice_amount' => '0',
            'global_tax_other_amount' => '0',
            'extra_costs_amount' => '0.25',
            'notes' => 'Compra actualizada',
            'items' => [
                [
                    'line_type' => 'normal',
                    'variant_id' => $variant->id,
                    'quantity' => 2,
                    'bonus_quantity' => 1,
                    'unit_cost' => '3.00',
                    'line_discount_amount' => '0.20',
                    'tax_iva_amount' => '0.30',
                    'tax_ice_amount' => '0',
                    'tax_other_amount' => '0',
                    'eligible_for_global_iva' => '1',
                ],
            ],
        ]);

        $response->assertRedirect(route('purchases.index'));
        $response->assertSessionHasNoErrors();

        $purchase->refresh();

        $this->assertSame('transfer', $purchase->payment_type);
        $this->assertSame('Compra actualizada', $purchase->notes);
        $this->assertSame(600, $purchase->subtotal_amount);
        $this->assertSame(585, $purchase->total_amount);
        $this->assertCount(1, $purchase->items);
        $this->assertCount(1, $purchase->lots);
        $this->assertSame('3.000', $purchase->lots()->firstOrFail()->initial_quantity);
    }

    #[Test]
    public function it_blocks_editing_a_detailed_purchase_when_any_lot_has_been_consumed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $purchase = $this->createDetailedPurchase($user);
        $lot = $purchase->lots()->firstOrFail();

        InventoryMovement::query()->create([
            'variant_id' => $lot->variant_id,
            'lot_id' => $lot->id,
            'movement_type' => 'sale_exit',
            'quantity' => -1,
            'unit_cost_amount' => $lot->unit_cost_final_amount,
            'reference_type' => 'sale',
            'reference_id' => 1000,
            'movement_at' => now(),
            'notes' => 'Consumo para bloquear edición',
            'created_by' => $user->id,
        ]);

        $response = $this->from(route('purchases.index'))->get(route('purchases.detailed.edit', $purchase));

        $response->assertRedirect(route('purchases.index'));
        $response->assertSessionHasErrors('purchase');
    }

    #[Test]
    public function it_blocks_duplicate_invoice_number_for_the_same_supplier(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $purchase = $this->createDetailedPurchase($user);
        [$variant] = $this->createVariants();

        $response = $this->from(route('purchases.detailed.create'))->post(route('purchases.detailed.store'), [
            'supplier_id' => $purchase->supplier_id,
            'invoice_number' => $purchase->invoice_number,
            'purchased_at' => '2026-04-23 09:00:00',
            'payment_type' => 'cash',
            'global_discount_amount' => '0',
            'global_tax_iva_amount' => '0',
            'global_tax_ice_amount' => '0',
            'global_tax_other_amount' => '0',
            'extra_costs_amount' => '0',
            'items' => [
                [
                    'line_type' => 'normal',
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                    'bonus_quantity' => 0,
                    'unit_cost' => '1.00',
                    'line_discount_amount' => '0',
                    'tax_iva_amount' => '0',
                    'tax_ice_amount' => '0',
                    'tax_other_amount' => '0',
                ],
            ],
        ]);

        $response->assertRedirect(route('purchases.detailed.create'));
        $response->assertSessionHasErrors('invoice_number');
    }

    private function createDetailedPurchase(User $user): Purchase
    {
        $supplier = Supplier::query()->create(['name' => 'Proveedor Base', 'is_active' => true]);
        [$variant] = $this->createVariants();

        $response = $this->post(route('purchases.detailed.store'), [
            'supplier_id' => $supplier->id,
            'invoice_number' => fake()->unique()->numerify('FAC-###'),
            'purchased_at' => '2026-04-21 12:00:00',
            'payment_type' => 'cash',
            'global_discount_amount' => '0',
            'global_tax_iva_amount' => '0',
            'global_tax_ice_amount' => '0',
            'global_tax_other_amount' => '0',
            'extra_costs_amount' => '0',
            'notes' => 'Compra base',
            'items' => [
                [
                    'line_type' => 'normal',
                    'variant_id' => $variant->id,
                    'quantity' => 3,
                    'bonus_quantity' => 0,
                    'unit_cost' => '2.50',
                    'line_discount_amount' => '0',
                    'tax_iva_amount' => '0',
                    'tax_ice_amount' => '0',
                    'tax_other_amount' => '0',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        return Purchase::query()->latest('id')->firstOrFail();
    }

    /**
     * @return array<int, ProductVariant>
     */
    private function createVariants(): array
    {
        $category = Category::query()->create(['name' => fake()->unique()->word(), 'is_active' => true]);
        $brand = Brand::query()->create(['name' => fake()->unique()->word(), 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => fake()->unique()->word(), 'symbol' => fake()->lexify('??')]);

        $productA = Product::query()->create([
            'name' => fake()->unique()->words(2, true),
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);

        $variantA = ProductVariant::query()->create([
            'product_id' => $productA->id,
            'name' => 'Variante A',
            'base_unit_id' => $baseUnit->id,
            'tracks_expiration' => false,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        $productB = Product::query()->create([
            'name' => fake()->unique()->words(2, true),
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);

        $variantB = ProductVariant::query()->create([
            'product_id' => $productB->id,
            'name' => 'Variante B',
            'base_unit_id' => $baseUnit->id,
            'tracks_expiration' => false,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        return [$variantA, $variantB];
    }
}
