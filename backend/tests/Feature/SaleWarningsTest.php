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

class SaleWarningsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_requires_explicit_confirmation_for_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $setup = $this->createPresentationScenario($user, availableQuantity: 1, unitCost: 300);

        $response = $this->from(route('sales.create'))->post(route('sales.store'), [
            'sold_at' => now()->format('Y-m-d H:i:s'),
            'payments' => ['cash' => '10.00', 'transfer' => '0'],
            'items' => [[
                'sale_presentation_id' => $setup['presentation']->id,
                'quantity' => 2,
            ]],
        ]);

        $response->assertRedirect(route('sales.create'));
        $response->assertSessionHasErrors('warnings');
        $this->assertDatabaseCount('sales', 0);
    }

    #[Test]
    public function it_allows_sale_with_insufficient_stock_when_confirmation_is_checked(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $setup = $this->createPresentationScenario($user, availableQuantity: 1, unitCost: 300);

        $response = $this->post(route('sales.store'), [
            'sold_at' => now()->format('Y-m-d H:i:s'),
            'confirm_stock_warnings' => '1',
            'confirm_cost_warnings' => '1',
            'payments' => ['cash' => '10.00', 'transfer' => '0'],
            'items' => [[
                'sale_presentation_id' => $setup['presentation']->id,
                'quantity' => 2,
            ]],
        ]);

        $response->assertRedirect(route('sales.index'));
        $response->assertSessionHasNoErrors();

        $item = Sale::query()->firstOrFail()->items()->firstOrFail();
        $this->assertTrue($item->has_stock_warning);
        $this->assertTrue($item->stock_warning_acknowledged);
        $this->assertTrue($item->cost_warning_acknowledged);
    }

    #[Test]
    public function it_requires_explicit_confirmation_when_cost_is_pending(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $setup = $this->createPresentationScenario($user, availableQuantity: 0, unitCost: 300);

        $response = $this->from(route('sales.create'))->post(route('sales.store'), [
            'sold_at' => now()->format('Y-m-d H:i:s'),
            'confirm_stock_warnings' => '1',
            'payments' => ['cash' => '5.00', 'transfer' => '0'],
            'items' => [[
                'sale_presentation_id' => $setup['presentation']->id,
                'quantity' => 1,
            ]],
        ]);

        $response->assertRedirect(route('sales.create'));
        $response->assertSessionHasErrors('warnings');
        $this->assertDatabaseCount('sales', 0);
    }

    private function createPresentationScenario(User $user, float $availableQuantity, int $unitCost): array
    {
        $category = Category::query()->create(['name' => 'Warning Category', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Warning Brand', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $product = Product::query()->create([
            'name' => 'Producto Warning',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Variante Warning',
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
            'reason' => 'Precio base warning',
        ]);

        CashSession::query()->create([
            'opened_by' => $user->id,
            'opened_at' => now()->subHour(),
            'opening_amount' => 1000,
            'status' => 'open',
        ]);

        if ($availableQuantity > 0) {
            InventoryLot::query()->create([
                'variant_id' => $variant->id,
                'origin_type' => 'purchase',
                'origin_id' => 1,
                'received_at' => now()->subDays(2),
                'initial_quantity' => $availableQuantity,
                'available_quantity' => $availableQuantity,
                'bonus_quantity' => 0,
                'unit_cost_final_amount' => $unitCost,
                'is_estimated' => false,
                'status' => 'active',
            ]);
        }

        return [
            'presentation' => $presentation,
            'variant' => $variant,
        ];
    }
}
