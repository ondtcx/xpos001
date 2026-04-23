<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseCreateUxTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_mode_guidance_in_quick_purchase_create(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->createVariant();

        $response = $this->get(route('purchases.create'));

        $response->assertOk();
        $response->assertSee('Compra rápida = velocidad operativa', false);
        $response->assertSee('Compra detallada = mayor fidelidad', false);
        $response->assertSee('Estado operativo', false);
        $response->assertSee('Siguiente paso sugerido', false);
    }

    #[Test]
    public function it_shows_mode_guidance_in_detailed_purchase_create(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->createVariant();

        $response = $this->get(route('purchases.detailed.create'));

        $response->assertOk();
        $response->assertSee('Compra detallada = fidelidad y trazabilidad', false);
        $response->assertSee('Compra rápida = cuando el caso es simple', false);
        $response->assertSee('Lectura del resumen', false);
        $response->assertSee('Tipo de líneas', false);
        $response->assertSee('Siguiente paso sugerido', false);
    }

    private function createVariant(): void
    {
        $category = Category::query()->create(['name' => 'Categoría UX Compra', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Marca UX Compra', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $product = Product::query()->create([
            'name' => 'Producto UX Compra',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Variante UX Compra',
            'base_unit_id' => $baseUnit->id,
            'tracks_expiration' => false,
            'is_returnable' => false,
            'is_active' => true,
        ]);
    }
}
