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
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseShowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_operational_purchase_detail_with_lines_lots_and_consumption_visibility(): void
    {
        $user = User::factory()->create(['name' => 'Comprador Uno']);
        $this->actingAs($user);

        $supplier = Supplier::query()->create(['name' => 'Proveedor Auditoría', 'is_active' => true]);
        $category = Category::query()->create(['name' => 'Categoría Compra', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Marca Compra', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $product = Product::query()->create([
            'name' => 'Producto Compra',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Variante Compra',
            'base_unit_id' => $baseUnit->id,
            'tracks_expiration' => true,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        $purchase = Purchase::query()->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'FAC-SHOW-01',
            'purchased_at' => now()->subHour(),
            'payment_type' => 'transfer',
            'entry_mode' => Purchase::ENTRY_MODE_DETAILED,
            'subtotal_amount' => 2000,
            'global_discount_amount' => 100,
            'global_tax_iva_amount' => 200,
            'global_tax_ice_amount' => 0,
            'global_tax_other_amount' => 50,
            'extra_costs_amount' => 75,
            'total_amount' => 2225,
            'notes' => 'Compra para auditoría',
            'status' => Purchase::STATUS_CONFIRMED,
            'created_by' => $user->id,
        ]);

        $item = PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'variant_id' => $variant->id,
            'line_type' => PurchaseItem::LINE_TYPE_NORMAL,
            'quantity' => 10,
            'bonus_quantity' => 2,
            'unit_cost_base_amount' => 200,
            'line_subtotal_amount' => 2000,
            'line_discount_amount' => 50,
            'tax_iva_amount' => 120,
            'tax_other_amount' => 20,
            'allocated_global_discount_amount' => 50,
            'allocated_global_tax_iva_amount' => 80,
            'allocated_global_tax_other_amount' => 30,
            'allocated_extra_costs_amount' => 75,
            'unit_cost_final_amount' => 188,
            'total_cost_amount' => 2255,
            'expiration_date' => now()->addMonths(3)->toDateString(),
            'notes' => 'Línea principal',
        ]);

        $lot = InventoryLot::query()->create([
            'variant_id' => $variant->id,
            'purchase_item_id' => $item->id,
            'origin_type' => 'purchase',
            'origin_id' => $purchase->id,
            'received_at' => now()->subHour(),
            'expiration_date' => now()->addMonths(3)->toDateString(),
            'initial_quantity' => 12,
            'available_quantity' => 9,
            'bonus_quantity' => 2,
            'unit_cost_final_amount' => 188,
            'is_estimated' => false,
            'status' => 'active',
        ]);

        InventoryMovement::query()->create([
            'variant_id' => $variant->id,
            'lot_id' => $lot->id,
            'movement_type' => 'purchase_entry',
            'quantity' => 12,
            'unit_cost_amount' => 188,
            'reference_type' => 'purchase',
            'reference_id' => $purchase->id,
            'movement_at' => now()->subHour(),
            'created_by' => $user->id,
        ]);

        InventoryMovement::query()->create([
            'variant_id' => $variant->id,
            'lot_id' => $lot->id,
            'movement_type' => 'sale_output',
            'quantity' => -3,
            'unit_cost_amount' => 188,
            'reference_type' => 'sale',
            'reference_id' => 999,
            'movement_at' => now()->subMinutes(20),
            'notes' => 'Consumo posterior',
            'created_by' => $user->id,
        ]);

        $response = $this->get(route('purchases.show', $purchase));

        $response->assertOk();
        $response->assertSee('Detalle de compra #' . $purchase->id, false);
        $response->assertSee('Proveedor Auditoría', false);
        $response->assertSee('Compra para auditoría', false);
        $response->assertSee('Detallada', false);
        $response->assertSee('Líneas de compra', false);
        $response->assertSee('Lotes creados', false);
        $response->assertSee('Consumo posterior', false);
        $response->assertSee('sale_output', false);
        $response->assertSee('FAC-SHOW-01', false);
        $response->assertSee('$22.25', false);
        $response->assertSee('$0.75', false);
    }
}
