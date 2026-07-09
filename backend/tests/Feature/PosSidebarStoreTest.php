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

    private const STORE_JS = __DIR__.'/../../resources/js/pos-sidebar-store.js';

    private const APP_JS = __DIR__.'/../../resources/js/app.js';

    #[Test]
    public function sidebar_uses_x_data_attribute_without_inline_state(): void
    {
        $content = $this->renderPosIndex();

        $this->assertStringContainsString('<aside x-data', $content);
        $this->assertStringNotContainsString('<aside x-data="{', $content);
        $this->assertStringNotContainsString('togglePanel(name) {', $content);
        $this->assertStringNotContainsString('fiadoAutoEnabled: @json', $content);
    }

    #[Test]
    public function sidebar_references_possidebar_store_and_initial_injection(): void
    {
        $content = $this->renderPosIndex();

        $this->assertStringContainsString('$store.posSidebar', $content);
        $this->assertStringContainsString('__POS_INITIAL__', $content);
    }

    #[Test]
    public function app_js_registers_possidebar_store(): void
    {
        $appJs = file_get_contents(self::APP_JS);

        $this->assertStringContainsString('registerPosSidebarStore', $appJs);
        $this->assertStringContainsString('Alpine.start', $appJs);
    }

    #[Test]
    public function initial_state_exposes_empty_used_panels(): void
    {
        $store = file_get_contents(self::STORE_JS);

        $this->assertStringContainsString('usedPanels: []', $store);
    }

    #[Test]
    public function customer_button_binds_active_state_to_store(): void
    {
        $content = $this->renderPosIndex();

        $this->assertStringContainsString("togglePanel('customer')", $content);
        $this->assertStringContainsString("\$store.posSidebar.isButtonActive('customer')", $content);
    }

    #[Test]
    public function payment_button_binds_active_state_to_store(): void
    {
        $content = $this->renderPosIndex();

        $this->assertStringContainsString("togglePanel('payment')", $content);
        $this->assertStringContainsString("\$store.posSidebar.isButtonActive('payment')", $content);
    }

    #[Test]
    public function store_exposes_required_methods_and_state(): void
    {
        $store = file_get_contents(self::STORE_JS);

        $this->assertStringContainsString('activePanel: null', $store);
        $this->assertStringContainsString('pinnedPanels: []', $store);
        $this->assertStringContainsString('usedPanels: []', $store);
        $this->assertStringContainsString('creditActive:', $store);
        $this->assertStringContainsString('paymentMethod:', $store);
        $this->assertStringContainsString('selectedCustomerId:', $store);
        $this->assertStringContainsString('selectedCustomerName:', $store);
        $this->assertStringContainsString('fiadoAutoEnabled:', $store);
        $this->assertStringContainsString('customerQuery:', $store);
        $this->assertStringContainsString('customerHighlightIndex: -1', $store);

        $this->assertStringContainsString('togglePanel(name)', $store);
        $this->assertStringContainsString('togglePin(name)', $store);
        $this->assertStringContainsString('syncToHiddenInputs()', $store);
        $this->assertStringContainsString('handleCreditToggle()', $store);

        $this->assertStringContainsString('isButtonActive(name)', $store);
        $this->assertStringContainsString('isButtonUsed(name)', $store);
        $this->assertStringContainsString('isPanelVisible(name)', $store);
    }

    private function renderPosIndex(): string
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->seedMinimalScenario($user);

        return $this->get(route('pos.index'))->getContent();
    }

    private function seedMinimalScenario(User $user): void
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
            'created_by' => $user->id,
            'reason' => 'Precio POS',
        ]);
    }
}
