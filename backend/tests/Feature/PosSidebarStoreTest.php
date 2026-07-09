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

class PosSidebarStoreTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_sidebar_uses_x_data_attribute_without_inline_object(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->createPresentationScenario();

        $response = $this->get(route('pos.index'));

        $response->assertOk();
        // Positive: the <aside> still declares the Alpine x-data attribute.
        $response->assertSee('<aside x-data', false);
        // Negative sanity: the inline state object and the inline togglePanel
        // function body MUST NOT appear in the rendered HTML anymore.
        $response->assertDontSee('activePanel: null,', false);
        $response->assertDontSee('togglePanel(name) {', false);
    }

    #[Test]
    public function test_sidebar_references_possidebar_store(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->createPresentationScenario();

        $response = $this->get(route('pos.index'));

        $response->assertOk();
        // The Blade MUST bind at least one element to the Alpine store by name.
        $response->assertSee('$store.posSidebar', false);
        // The store MUST be seeded with a window.__POS_INITIAL__ payload
        // so the JS module can read server-side state on init.
        $response->assertSee('window.__POS_INITIAL__', false);
    }

    #[Test]
    public function test_app_js_registers_possidebar_store(): void
    {
        $appJsPath = base_path('resources/js/app.js');

        $this->assertFileExists($appJsPath, 'app.js should exist for Alpine boot');

        $appJs = file_get_contents($appJsPath);
        $this->assertNotFalse($appJs, 'app.js should be readable');

        $this->assertStringContainsString('registerPosSidebarStore', $appJs);
        $this->assertStringContainsString('Alpine.start()', $appJs);
    }

    #[Test]
    public function test_initial_state_has_no_used_panels(): void
    {
        $storeJsPath = base_path('resources/js/pos-sidebar-store.js');

        $this->assertFileExists($storeJsPath, 'pos-sidebar-store.js should exist');

        $storeJs = file_get_contents($storeJsPath);
        $this->assertNotFalse($storeJs, 'pos-sidebar-store.js should be readable');

        // The store MUST expose `usedPanels` so PR 2 (reactivation) can
        // populate it additively without breaking the public contract.
        $this->assertStringContainsString('usedPanels', $storeJs);
    }

    private function createPresentationScenario(): SalePresentation
    {
        $category = Category::query()->create(['name' => 'Categoría POS', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Marca POS', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $product = Product::query()->create([
            'name' => 'Producto POS',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'internal_code' => 'POS-001',
            'status' => 'active',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Variante POS',
            'barcode' => '7891234567890',
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
            'created_by' => 1,
            'reason' => 'Precio POS',
        ]);

        return $presentation;
    }
}
