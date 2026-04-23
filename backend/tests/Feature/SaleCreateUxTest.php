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

class SaleCreateUxTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_operational_summary_checklist_and_quick_navigation_in_sale_create(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->createPresentationScenario($user);

        $response = $this->get(route('sales.create'));

        $response->assertOk();
        $response->assertSee('Estado operativo de la venta', false);
        $response->assertSee('Checklist operativo', false);
        $response->assertSee('Señales a revisar', false);
        $response->assertSee('Composición de cierre', false);
        $response->assertSee('Líneas listas / pendientes', false);
        $response->assertSee('Siguiente paso sugerido', false);
        $response->assertSee('Ver caja actual', false);
        $response->assertSee('Cobranza', false);
        $response->assertSee('Disponibilidad estimada', false);
    }

    private function createPresentationScenario(User $user): void
    {
        $category = Category::query()->create(['name' => 'Categoría UX', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Marca UX', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $product = Product::query()->create([
            'name' => 'Producto UX',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'internal_code' => 'UX-001',
            'status' => 'active',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Variante UX',
            'barcode' => '123456',
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
            'reason' => 'Precio base UX',
        ]);
    }
}
