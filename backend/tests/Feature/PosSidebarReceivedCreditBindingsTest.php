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

/**
 * PR 1b (second half of the 1a/1b split) — bindings for the remaining two
 * contextual buttons: "Ingresar monto recibido" and "Convertir a fiado".
 *
 * The store already exposes creditActive, fiadoAutoEnabled, and
 * handleCreditToggle() from PR 1a. PR 1b wires the received/credit buttons
 * to use isButtonActive(...) and adds a receivedAmount reactive state to
 * the store.
 */
class PosSidebarReceivedCreditBindingsTest extends TestCase
{
    use RefreshDatabase;

    private const STORE_JS = __DIR__.'/../../resources/js/pos-sidebar-store.js';

    #[Test]
    public function received_button_binds_active_state_to_store(): void
    {
        $content = $this->renderPosIndex();

        $this->assertStringContainsString("togglePanel('received')", $content);
        $this->assertStringContainsString("\$store.posSidebar.isButtonActive('received')", $content);
    }

    #[Test]
    public function credit_button_binds_active_state_to_store(): void
    {
        $content = $this->renderPosIndex();

        $this->assertStringContainsString('handleCreditToggle()', $content);
        $this->assertStringContainsString("\$store.posSidebar.isButtonActive('credit')", $content);
    }

    #[Test]
    public function store_exposes_received_amount_state(): void
    {
        $store = file_get_contents(self::STORE_JS);

        $this->assertStringContainsString('receivedAmount:', $store);
    }

    #[Test]
    public function received_amount_input_binds_to_store(): void
    {
        $content = $this->renderPosIndex();

        $this->assertStringContainsString('id="pos-received-amount"', $content);
        $this->assertStringContainsString('x-model="$store.posSidebar.receivedAmount"', $content);
    }

    #[Test]
    public function received_and_credit_panels_use_panel_visible_getter(): void
    {
        $content = $this->renderPosIndex();

        $this->assertStringContainsString("isPanelVisible('received')", $content);
        $this->assertStringContainsString("isPanelVisible('credit')", $content);
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
