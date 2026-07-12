<?php

namespace Tests\Feature\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductsIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_products_index_page_loads_successfully(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('products.index'));

        $response->assertOk();
    }

    #[Test]
    public function it_displays_the_products_title(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('products.index'));

        $response->assertSee('Productos', false);
    }

    #[Test]
    public function it_has_a_create_product_link(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('products.index'));

        $response->assertSee(route('products.create'), false);
    }

    #[Test]
    public function it_displays_seeded_products(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::query()->create([
            'name' => 'Bebidas',
            'is_active' => true,
        ]);
        $brand = Brand::query()->create([
            'name' => 'Coca-Cola',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'name' => 'Coca-Cola 500ml',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'internal_code' => 'CC-500',
        ]);

        $response = $this->get(route('products.index'));

        $response->assertSee('Coca-Cola 500ml', false);
        $response->assertSee('CC-500', false);
        $response->assertSee('Bebidas', false);
        $response->assertSee('Coca-Cola', false);
    }

    #[Test]
    public function it_shows_an_empty_state_when_no_products_exist(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Aún no hay productos registrados.', false);
    }

    #[Test]
    public function it_does_not_use_legacy_indigo_color(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('products.index'));

        // Scoped assertion: check products-specific patterns that previously used indigo.
        //   - bg-indigo-600: create button
        //   - text-indigo-600: "Variantes" link and "Editar" link
        //   - hover:text-indigo-800: link hover styles
        $response->assertDontSee('bg-indigo-600', false);
        $response->assertDontSee('text-indigo-600', false);
        $response->assertDontSee('hover:text-indigo-800', false);
    }
}
