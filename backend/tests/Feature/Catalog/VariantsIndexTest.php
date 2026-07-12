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

class VariantsIndexTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(): Product
    {
        $category = Category::query()->create([
            'name' => 'Bebidas',
            'is_active' => true,
        ]);
        $brand = Brand::query()->create([
            'name' => 'Coca-Cola',
            'is_active' => true,
        ]);

        return Product::query()->create([
            'name' => 'Coca-Cola 500ml',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);
    }

    #[Test]
    public function the_variants_index_page_loads_successfully(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = $this->createProduct();

        $response = $this->get(route('products.variants.index', $product));

        $response->assertOk();
    }

    #[Test]
    public function it_displays_the_variants_title(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = $this->createProduct();

        $response = $this->get(route('products.variants.index', $product));

        $response->assertSee('Variantes de ' . $product->name, false);
    }

    #[Test]
    public function it_has_a_create_variant_link(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = $this->createProduct();

        $response = $this->get(route('products.variants.index', $product));

        $response->assertSee(route('products.variants.create', $product), false);
    }

    #[Test]
    public function it_displays_seeded_variants(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = $this->createProduct();
        $baseUnit = BaseUnit::query()->create([
            'name' => 'Unidad',
            'symbol' => 'ud',
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Botella 500ml',
            'base_unit_id' => $baseUnit->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('products.variants.index', $product));

        $response->assertSee('Botella 500ml', false);
    }

    #[Test]
    public function it_shows_an_empty_state_when_no_variants_exist(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = $this->createProduct();

        $response = $this->get(route('products.variants.index', $product));

        $response->assertOk();
        $response->assertSee('Aún no hay variantes registradas.', false);
    }

    #[Test]
    public function it_does_not_use_legacy_indigo_color(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = $this->createProduct();

        $response = $this->get(route('products.variants.index', $product));

        // Scoped assertion: check variants-specific patterns that previously used indigo.
        //   - bg-indigo-600: create button
        //   - text-indigo-600: "← Volver a productos", "Presentaciones", "Editar" links
        //   - hover:text-indigo-800: link hover styles
        $response->assertDontSee('bg-indigo-600', false);
        $response->assertDontSee('text-indigo-600', false);
        $response->assertDontSee('hover:text-indigo-800', false);
    }
}
