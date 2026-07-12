<?php

namespace Tests\Feature\Catalog;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalePresentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PricesIndexTest extends TestCase
{
    use RefreshDatabase;

    private function createPresentation(): array
    {
        $category = Category::query()->create(["name" => "Bebidas", "is_active" => true]);
        $brand = Brand::query()->create(["name" => "Coca-Cola", "is_active" => true]);
        $product = Product::query()->create(["name" => "Coca-Cola 500ml", "category_id" => $category->id, "brand_id" => $brand->id, "status" => "active"]);
        $baseUnit = BaseUnit::query()->create(["name" => "Unidad", "symbol" => "ud"]);
        $variant = ProductVariant::query()->create(["product_id" => $product->id, "name" => "Botella 500ml", "base_unit_id" => $baseUnit->id, "is_active" => true]);
        $presentation = SalePresentation::query()->create(["product_variant_id" => $variant->id, "name" => "Botella 500ml", "conversion_factor" => 1.000, "is_default" => true, "is_active" => true]);
        return [$product, $variant, $presentation];
    }

    #[Test]
    public function the_prices_index_page_loads_successfully(): void
    {
        $user = User::factory()->create(); $this->actingAs($user);
        [$product, $variant, $presentation] = $this->createPresentation();
        $response = $this->get(route("products.variants.presentations.prices.index", [$product, $variant, $presentation]));
        $response->assertOk();
    }

    #[Test]
    public function it_displays_the_prices_title(): void
    {
        $user = User::factory()->create(); $this->actingAs($user);
        [$product, $variant, $presentation] = $this->createPresentation();
        $response = $this->get(route("products.variants.presentations.prices.index", [$product, $variant, $presentation]));
        $response->assertSee("Historial de precios", false);
    }

    #[Test]
    public function it_has_a_create_price_link(): void
    {
        $user = User::factory()->create(); $this->actingAs($user);
        [$product, $variant, $presentation] = $this->createPresentation();
        $response = $this->get(route("products.variants.presentations.prices.index", [$product, $variant, $presentation]));
        $response->assertSee(route("products.variants.presentations.prices.create", [$product, $variant, $presentation]), false);
    }

    #[Test]
    public function it_displays_seeded_prices(): void
    {
        $user = User::factory()->create(); $this->actingAs($user);
        [$product, $variant, $presentation] = $this->createPresentation();
        $price = $presentation->prices()->create([
            "price_amount" => 1500.00,
            "starts_at" => now(),
            "created_by" => $user->id,
        ]);
        $response = $this->get(route("products.variants.presentations.prices.index", [$product, $variant, $presentation]));
        $response->assertSee("\$15.00", false);
    }

    #[Test]
    public function it_shows_an_empty_state_when_no_prices_exist(): void
    {
        $user = User::factory()->create(); $this->actingAs($user);
        [$product, $variant, $presentation] = $this->createPresentation();
        $response = $this->get(route("products.variants.presentations.prices.index", [$product, $variant, $presentation]));
        $response->assertOk();
        $response->assertSee("Aún no hay precios registrados.", false);
    }

    #[Test]
    public function it_does_not_use_legacy_indigo_color(): void
    {
        $user = User::factory()->create(); $this->actingAs($user);
        [$product, $variant, $presentation] = $this->createPresentation();
        $response = $this->get(route("products.variants.presentations.prices.index", [$product, $variant, $presentation]));
        // Scoped assertion: check prices-specific patterns that previously used indigo.
        //   - bg-indigo-600: create button
        //   - text-indigo-600: "← Volver a presentaciones" link
        //   - hover:text-indigo-800: link hover styles
        $response->assertDontSee("bg-indigo-600", false);
        $response->assertDontSee("text-indigo-600", false);
        $response->assertDontSee("hover:text-indigo-800", false);
    }
}

