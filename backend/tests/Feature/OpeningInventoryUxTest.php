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

class OpeningInventoryUxTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_operational_panel_in_opening_inventory_form(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->createVariant();

        $response = $this->get(route('opening-inventory.create'));

        $response->assertOk();
        $response->assertSee('Estado operativo', false);
        $response->assertSee('Resumen monetario', false);
        $response->assertSee('Siguiente paso sugerido', false);
        $response->assertSee('Ver lotes', false);
    }

    #[Test]
    public function it_shows_recent_opening_context_after_registering_inventory(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $variant = $this->createVariant();

        $response = $this->post(route('opening-inventory.store'), [
            'variant_id' => $variant->id,
            'quantity' => 5,
            'estimated_unit_cost' => '2.50',
            'recorded_at' => now()->format('Y-m-d H:i:s'),
            'is_audited' => '1',
        ]);

        $response->assertRedirect(route('opening-inventory.index'));

        $followUp = $this->get(route('opening-inventory.index'));
        $followUp->assertOk();
        $followUp->assertSee('Último registro creado', false);
        $followUp->assertSee('Revisar lotes', false);
        $followUp->assertSee('Auditadas', false);
    }

    private function createVariant(): ProductVariant
    {
        $category = Category::query()->create(['name' => 'Categoría Inicial', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Marca Inicial', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $product = Product::query()->create([
            'name' => 'Producto Inicial',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);

        return ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Variante Inicial',
            'base_unit_id' => $baseUnit->id,
            'tracks_expiration' => false,
            'is_returnable' => false,
            'is_active' => true,
        ]);
    }
}
