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
use App\Models\SupplierVariantRef;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuickPurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_quick_purchase_with_items_lots_movements_and_supplier_reference(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::query()->create(['name' => 'Proveedor Rápido', 'is_active' => true]);
        [$variantA, $variantB] = $this->createVariants();

        $response = $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'FAC-QUICK-001',
            'purchased_at' => '2026-04-22 09:30:00',
            'payment_type' => 'cash',
            'is_credit' => false,
            'notes' => 'Compra rápida de prueba',
            'items' => [
                [
                    'variant_id' => $variantA->id,
                    'quantity' => 3,
                    'unit_cost' => '2.50',
                    'expiration_date' => '2026-12-31',
                    'notes' => 'Línea A',
                ],
                [
                    'variant_id' => $variantB->id,
                    'quantity' => 2,
                    'unit_cost' => '1.75',
                    'notes' => 'Línea B',
                ],
            ],
        ]);

        $response->assertRedirect(route('purchases.index'));
        $response->assertSessionHasNoErrors();

        $purchase = Purchase::query()->with(['items', 'lots'])->firstOrFail();

        $this->assertSame(Purchase::ENTRY_MODE_QUICK, $purchase->entry_mode);
        $this->assertSame(1100, $purchase->subtotal_amount);
        $this->assertSame(1100, $purchase->total_amount);
        $this->assertFalse($purchase->is_credit);
        $this->assertCount(2, $purchase->items);
        $this->assertCount(2, $purchase->lots);

        $lotA = InventoryLot::query()->where('variant_id', $variantA->id)->firstOrFail();
        $lotB = InventoryLot::query()->where('variant_id', $variantB->id)->firstOrFail();

        $this->assertSame('3.000', $lotA->initial_quantity);
        $this->assertSame(250, $lotA->unit_cost_final_amount);
        $this->assertSame('2.000', $lotB->initial_quantity);
        $this->assertSame(175, $lotB->unit_cost_final_amount);
        $this->assertDatabaseCount('inventory_movements', 2);

        $supplierRefA = SupplierVariantRef::query()
            ->where('supplier_id', $supplier->id)
            ->where('variant_id', $variantA->id)
            ->firstOrFail();

        $supplierRefB = SupplierVariantRef::query()
            ->where('supplier_id', $supplier->id)
            ->where('variant_id', $variantB->id)
            ->firstOrFail();

        $this->assertSame(250, $supplierRefA->last_purchase_price_amount);
        $this->assertSame(175, $supplierRefB->last_purchase_price_amount);
    }

    #[Test]
    public function it_creates_quick_purchase_without_supplier_reference_when_supplier_is_missing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        [$variant] = $this->createVariants();

        $response = $this->post(route('purchases.store'), [
            'purchased_at' => '2026-04-22 11:00:00',
            'payment_type' => 'transfer',
            'is_credit' => true,
            'items' => [
                [
                    'variant_id' => $variant->id,
                    'quantity' => 4,
                    'unit_cost' => '1.25',
                ],
            ],
        ]);

        $response->assertRedirect(route('purchases.index'));
        $response->assertSessionHasNoErrors();

        $purchase = Purchase::query()->firstOrFail();

        $this->assertNull($purchase->supplier_id);
        $this->assertTrue($purchase->is_credit);
        $this->assertSame(500, $purchase->total_amount);
        $this->assertDatabaseCount('supplier_variant_refs', 0);

        $movement = InventoryMovement::query()->firstOrFail();
        $this->assertSame('purchase_entry', $movement->movement_type);
        $this->assertSame('4.000', $movement->quantity);
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
            'name' => 'Variante Quick A',
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
            'name' => 'Variante Quick B',
            'base_unit_id' => $baseUnit->id,
            'tracks_expiration' => false,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        return [$variantA, $variantB];
    }
}
