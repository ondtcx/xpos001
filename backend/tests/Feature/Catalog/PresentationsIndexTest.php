<?php

namespace Tests\Feature\Catalog;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PresentationsIndexTest extends TestCase
{
    use RefreshDatabase;

    private function createVariant(): array
    {
        $category = Category::query()->create(["name" => "Bebidas", "is_active" => true]);
        $brand = Brand::query()->create(["name" => "Coca-Cola", "is_active" => true]);
        $product = Product::query()->create(["name" => "Coca-Cola 500ml", "category_id" => $category->id, "brand_id" => $brand->id, "status" => "active"]);
        $baseUnit = BaseUnit::query()->create(["name" => "Unidad", "symbol" => "ud"]);
        $variant = ProductVariant::query()->create(["product_id" => $product->id, "name" => "Botella 500ml", "base_unit_id" => $baseUnit->id, "is_active" => true]);
        return [$product, $variant];
    }

    #[Test]
    public function the_presentations_index_page_loads_successfully(): void
    {
        $user = User::factory()->create(); $this->actingAs($user);
        [$product, $variant] = $this->createVariant();
        $response = $this->get(route("products.variants.presentations.index", [$product, $variant]));
        $response->assertOk();
    }

    #[Test]
    public function it_displays_the_presentations_title(): void
    {
        $user = User::factory()->create(); $this->actingAs($user);
        [$product, $variant] = $this->createVariant();
        $response = $this->get(route("products.variants.presentations.index", [$product, $variant]));
        $response->assertSee("Presentaciones de " . $variant->name, false);
    }

    #[Test]
    public function it_has_a_create_presentation_link(): void
    {
        $user = User::factory()->create(); $this->actingAs($user);
        [$product, $variant] = $this->createVariant();
        $response = $this->get(route("products.variants.presentations.index", [$product, $variant]));
        $response->assertSee(route("products.variants.presentations.create", [$product, $variant]), false);
    }

    #[Test]
    public function it_displays_seeded_presentations(): void
    {
        $user = User::factory()->create(); $this->actingAs($user);
        [$product, $variant] = $this->createVariant();
        $presentation = $variant->presentations()->create(["name" => "Botella 500ml", "conversion_factor" => 1.000, "is_default" => true, "is_active" => true]);
        $response = $this->get(route("products.variants.presentations.index", [$product, $variant]));
        $response->assertSee("Botella 500ml", false);
    }

    #[Test]
    public function it_shows_an_empty_state_when_no_presentations_exist(): void
    {
        $user = User::factory()->create(); $this->actingAs($user);
        [$product, $variant] = $this->createVariant();
        $response = $this->get(route("products.variants.presentations.index", [$product, $variant]));
        $response->assertOk();
        $response->assertSee("Aún no hay presentaciones registradas.", false);
    }

    #[Test]
    public function it_does_not_use_legacy_indigo_color(): void
    {
        $user = User::factory()->create(); $this->actingAs($user);
        [$product, $variant] = $this->createVariant();
        $response = $this->get(route("products.variants.presentations.index", [$product, $variant]));
        $response->assertDontSee("bg-indigo-600", false);
        $response->assertDontSee("text-indigo-600", false);
        $response->assertDontSee("hover:text-indigo-800", false);
    }
}
