<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportNavigationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_exposes_navigation_links_from_reports_to_operational_detail_views(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::query()->create(['name' => 'Cliente Navegación', 'is_active' => true]);
        $category = Category::query()->create(['name' => 'Categoría Nav', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Marca Nav', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $product = Product::query()->create([
            'name' => 'Producto Navegación',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Variante Nav',
            'base_unit_id' => $baseUnit->id,
            'tracks_expiration' => false,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        InventoryLot::query()->create([
            'variant_id' => $variant->id,
            'origin_type' => 'purchase',
            'origin_id' => 1,
            'received_at' => now()->subDay(),
            'initial_quantity' => 4,
            'available_quantity' => 4,
            'bonus_quantity' => 0,
            'unit_cost_final_amount' => 250,
            'is_estimated' => false,
            'status' => 'active',
        ]);

        CashSession::query()->create([
            'opened_by' => $user->id,
            'opened_at' => now()->subHours(2),
            'opening_amount' => 1000,
            'status' => 'closed',
            'closed_at' => now()->subHour(),
        ]);

        $sale = Sale::query()->create([
            'sold_at' => now()->subMinutes(30),
            'customer_id' => $customer->id,
            'subtotal_amount' => 500,
            'discount_amount' => 0,
            'total_amount' => 500,
            'paid_amount' => 300,
            'credit_amount' => 200,
            'status' => Sale::STATUS_CONFIRMED,
            'created_by' => $user->id,
        ]);

        $purchase = Purchase::query()->create([
            'purchased_at' => now()->subMinutes(45),
            'payment_type' => 'cash',
            'entry_mode' => Purchase::ENTRY_MODE_QUICK,
            'subtotal_amount' => 800,
            'total_amount' => 800,
            'status' => Purchase::STATUS_CONFIRMED,
            'created_by' => $user->id,
        ]);

        $receivable = Receivable::query()->create([
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'original_amount' => 200,
            'pending_amount' => 200,
            'opened_at' => now()->subMinutes(30),
            'status' => 'open',
        ]);

        $response = $this->get(route('reports.index', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee(route('sales.index'), false);
        $response->assertSee(route('purchases.index'), false);
        $response->assertSee(route('receivables.index'), false);
        $response->assertSee(route('inventory-lots.index'), false);
        $response->assertSee(route('sales.show', $sale), false);
        $response->assertSee(route('purchases.show', $purchase), false);
        $response->assertSee(route('receivables.show', $receivable), false);
    }
}
