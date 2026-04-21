<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SalePresentation;
use App\Models\SalePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaleManualPriceOverrideTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stores_manual_price_override_with_reason(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $setup = $this->createPresentationScenario($user);

        $response = $this->post(route('sales.store'), [
            'sold_at' => now()->format('Y-m-d H:i:s'),
            'payments' => ['cash' => '4.50', 'transfer' => '0'],
            'items' => [[
                'sale_presentation_id' => $setup['presentation']->id,
                'quantity' => 1,
                'manual_unit_price' => '4.50',
                'manual_price_reason' => 'Descuento comercial autorizado',
            ]],
        ]);

        $response->assertRedirect(route('sales.index'));
        $response->assertSessionHasNoErrors();

        $sale = Sale::query()->firstOrFail();
        $item = $sale->items()->firstOrFail();

        $this->assertTrue($item->hasManualPriceOverride());
        $this->assertSame(500, $item->original_unit_price_amount);
        $this->assertSame(450, $item->manual_unit_price_amount);
        $this->assertSame(450, $item->unit_price_amount);
        $this->assertSame('Descuento comercial autorizado', $item->manual_price_reason);
    }

    #[Test]
    public function it_blocks_manual_price_override_without_reason(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $setup = $this->createPresentationScenario($user);

        $response = $this->from(route('sales.create'))->post(route('sales.store'), [
            'sold_at' => now()->format('Y-m-d H:i:s'),
            'payments' => ['cash' => '4.50', 'transfer' => '0'],
            'items' => [[
                'sale_presentation_id' => $setup['presentation']->id,
                'quantity' => 1,
                'manual_unit_price' => '4.50',
                'manual_price_reason' => '',
            ]],
        ]);

        $response->assertRedirect(route('sales.create'));
        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('sales', 0);
    }

    #[Test]
    public function it_blocks_manual_price_below_cost(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $setup = $this->createPresentationScenario($user, unitCost: 450);

        $response = $this->from(route('sales.create'))->post(route('sales.store'), [
            'sold_at' => now()->format('Y-m-d H:i:s'),
            'payments' => ['cash' => '4.00', 'transfer' => '0'],
            'items' => [[
                'sale_presentation_id' => $setup['presentation']->id,
                'quantity' => 1,
                'manual_unit_price' => '4.00',
                'manual_price_reason' => 'Intento bajo costo',
            ]],
        ]);

        $response->assertRedirect(route('sales.create'));
        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('sales', 0);
    }

    private function createPresentationScenario(User $user, int $unitCost = 300): array
    {
        $category = Category::query()->create(['name' => 'Snacks', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Marca Test', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $product = Product::query()->create([
            'name' => 'Producto Override',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Presentación Principal',
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

        SalePrice::query()->create([
            'sale_presentation_id' => $presentation->id,
            'price_amount' => 500,
            'min_price_amount' => 400,
            'starts_at' => now()->subDay(),
            'created_by' => $user->id,
            'reason' => 'Precio base',
        ]);

        CashSession::query()->create([
            'opened_by' => $user->id,
            'opened_at' => now()->subHour(),
            'opening_amount' => 1000,
            'status' => 'open',
        ]);

        InventoryLot::query()->create([
            'variant_id' => $variant->id,
            'origin_type' => 'purchase',
            'origin_id' => 1,
            'received_at' => now()->subDays(2),
            'initial_quantity' => 10,
            'available_quantity' => 10,
            'bonus_quantity' => 0,
            'unit_cost_final_amount' => $unitCost,
            'is_estimated' => false,
            'status' => 'active',
        ]);

        return [
            'presentation' => $presentation,
            'variant' => $variant,
        ];
    }
}
