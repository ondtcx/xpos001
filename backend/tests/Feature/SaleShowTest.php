<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\SaleItemLotConsumption;
use App\Models\SalePayment;
use App\Models\SalePresentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaleShowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_operational_sale_detail_with_payments_receivable_and_lot_consumptions(): void
    {
        $user = User::factory()->create(['name' => 'Operador Uno']);
        $this->actingAs($user);

        $customer = Customer::query()->create(['name' => 'Cliente Auditoría', 'is_active' => true]);
        $category = Category::query()->create(['name' => 'Categoría', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Marca', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $product = Product::query()->create([
            'name' => 'Producto Auditoría',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'internal_code' => 'P-001',
            'status' => 'active',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Variante Auditoría',
            'barcode' => '123456789',
            'base_unit_id' => $baseUnit->id,
            'tracks_expiration' => false,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        $presentation = SalePresentation::query()->create([
            'product_variant_id' => $variant->id,
            'name' => 'Unidad',
            'conversion_factor' => 1,
            'is_default' => true,
            'is_active' => true,
        ]);

        $sale = Sale::query()->create([
            'sold_at' => now()->subMinutes(20),
            'customer_id' => $customer->id,
            'subtotal_amount' => 900,
            'discount_amount' => 0,
            'total_amount' => 900,
            'paid_amount' => 500,
            'credit_amount' => 400,
            'status' => Sale::STATUS_CONFIRMED,
            'notes' => 'Observación operativa',
            'created_by' => $user->id,
        ]);

        $item = $sale->items()->create([
            'item_type' => 'product',
            'sale_presentation_id' => $presentation->id,
            'variant_id' => $variant->id,
            'description_snapshot' => 'Producto Auditoría — Variante Auditoría — Unidad',
            'quantity' => 2,
            'unit_price_amount' => 450,
            'original_unit_price_amount' => 500,
            'manual_unit_price_amount' => 450,
            'has_manual_price_override' => true,
            'manual_price_reason' => 'Promoción manual',
            'subtotal_amount' => 900,
            'total_cost_amount' => 600,
            'total_profit_amount' => 300,
            'has_cost_warning' => false,
            'has_stock_warning' => true,
        ]);

        $lot = InventoryLot::query()->create([
            'variant_id' => $variant->id,
            'origin_type' => 'purchase',
            'origin_id' => 1,
            'received_at' => now()->subDays(2),
            'initial_quantity' => 10,
            'available_quantity' => 8,
            'bonus_quantity' => 0,
            'unit_cost_final_amount' => 300,
            'is_estimated' => false,
            'status' => 'active',
        ]);

        SaleItemLotConsumption::query()->create([
            'sale_item_id' => $item->id,
            'lot_id' => $lot->id,
            'quantity' => 2,
            'unit_cost_amount' => 300,
            'total_cost_amount' => 600,
        ]);

        SalePayment::query()->create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'amount' => 500,
            'received_at' => now()->subMinutes(20),
        ]);

        $receivable = Receivable::query()->create([
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'original_amount' => 400,
            'pending_amount' => 250,
            'opened_at' => now()->subMinutes(20),
            'status' => 'open',
        ]);

        ReceivablePayment::query()->create([
            'receivable_id' => $receivable->id,
            'amount' => 150,
            'payment_method' => 'transfer',
            'paid_at' => now()->subMinutes(10),
            'created_by' => $user->id,
        ]);

        $response = $this->get(route('sales.show', $sale));

        $response->assertOk();
        $response->assertSee('Detalle de venta #' . $sale->id, false);
        $response->assertSee('Cliente Auditoría', false);
        $response->assertSee('Observación operativa', false);
        $response->assertSee('Promoción manual', false);
        $response->assertSee('Override', false);
        $response->assertSee('Stock', false);
        $response->assertSee('Cobros de la venta', false);
        $response->assertSee('Cuenta por cobrar', false);
        $response->assertSee('Consumos por lote', false);
        $response->assertSee('Lote #' . $lot->id, false);
        $response->assertSee('$9.00', false);
        $response->assertSee('$5.00', false);
        $response->assertSee('$4.00', false);
    }
}
