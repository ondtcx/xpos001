<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalePresentation;
use App\Models\SalePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaleSearchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_results_for_partial_name_search(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $presentation = $this->createPresentation($user, 'Coca Cola', 'COCA-001', '1234567890');

        $response = $this->getJson(route('sales.search', ['q' => 'coca']));

        $response->assertOk()
            ->assertJsonPath('auto_select', false)
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $presentation->id)
            ->assertJsonPath('results.0.label', 'Coca Cola — Botella 500ml — Unidad');
    }

    #[Test]
    public function it_auto_selects_exact_internal_code_or_barcode_matches(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $presentation = $this->createPresentation($user, 'Pepsi', 'PEP-777', '99887766');

        $response = $this->getJson(route('sales.search', ['q' => '99887766']));

        $response->assertOk()
            ->assertJsonPath('auto_select', true)
            ->assertJsonPath('results.0.id', $presentation->id)
            ->assertJsonPath('results.0.exact_code_match', true)
            ->assertJsonPath('results.0.barcode', '99887766');
    }

    private function createPresentation(User $user, string $productName, string $internalCode, string $barcode): SalePresentation
    {
        $category = Category::query()->create(['name' => fake()->unique()->word(), 'is_active' => true]);
        $brand = Brand::query()->create(['name' => fake()->unique()->word(), 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $product = Product::query()->create([
            'name' => $productName,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'internal_code' => $internalCode,
            'status' => 'active',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Botella 500ml',
            'barcode' => $barcode,
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
            'price_amount' => 175,
            'min_price_amount' => 150,
            'starts_at' => now()->subDay(),
            'created_by' => $user->id,
            'reason' => 'Precio test',
        ]);

        return $presentation;
    }
}
