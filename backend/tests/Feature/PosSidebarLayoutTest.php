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
 * PR 4 — Layout vertical del sidebar.
 *
 * Cubre la spec `pos-sidebar-vertical-layout` y el escenario de pin
 * preservado. El sidebar debe envolver los 4 paneles contextuales
 * (customer, payment, received, credit) en un contenedor de altura
 * acotada con scroll interno, y cada panel debe poder scrollear su
 * contenido sin empujar al resto. La semántica de pin (panel
 * pineado permanece visible) no se toca.
 *
 * Tests snapshot del Blade renderizado. No tocan el store ni el JS.
 */
class PosSidebarLayoutTest extends TestCase
{
    use RefreshDatabase;

    private const PANEL_IDS = [
        'pos-customer-panel',
        'pos-payment-methods-panel',
        'pos-received-panel',
        'pos-credit-panel',
    ];

    #[Test]
    public function sidebar_wraps_panels_in_fixed_height_overflow_container(): void
    {
        $content = $this->renderPosIndex();

        $this->assertStringContainsString(
            'max-h-[calc(100vh-12rem)]',
            $content,
            'Sidebar wrapper is missing the fixed-height class.'
        );
        $this->assertStringContainsString(
            'max-h-[calc(100vh-12rem)] overflow-y-auto',
            $content,
            'Sidebar wrapper must combine max-h with overflow-y-auto on the same element.'
        );
    }

    #[Test]
    public function each_contextual_panel_has_inner_overflow(): void
    {
        $content = $this->renderPosIndex();

        foreach (self::PANEL_IDS as $panelId) {
            $chunk = $this->sliceFromPanelStartToNextMarker($content, $panelId);
            $this->assertStringContainsString(
                'overflow-y-auto',
                $chunk,
                "Panel '{$panelId}' is missing an inner overflow-y-auto container."
            );
        }
    }

    #[Test]
    public function pin_visibility_bindings_for_all_four_panels_are_preserved(): void
    {
        $content = $this->renderPosIndex();

        // Customer and payment use the long form (activePanel === 'X' || pinnedPanels.includes('X'))
        // — the layout PR must not refactor this into isPanelVisible (out of scope).
        $this->assertStringContainsString(
            "\$store.posSidebar.activePanel === 'customer' || \$store.posSidebar.pinnedPanels.includes('customer')",
            $content,
            'Customer panel x-show binding was modified; pin semantics may be broken.'
        );
        $this->assertStringContainsString(
            "\$store.posSidebar.activePanel === 'payment' || \$store.posSidebar.pinnedPanels.includes('payment')",
            $content,
            'Payment panel x-show binding was modified; pin semantics may be broken.'
        );

        // Received and credit use the isPanelVisible getter; binding text must stay verbatim.
        $this->assertStringContainsString(
            "\$store.posSidebar.isPanelVisible('received')",
            $content,
            'Received panel x-show binding was modified; pin semantics may be broken.'
        );
        $this->assertStringContainsString(
            "\$store.posSidebar.isPanelVisible('credit')",
            $content,
            'Credit panel x-show binding was modified; pin semantics may be broken.'
        );
    }

    #[Test]
    public function all_four_panels_sit_inside_the_height_capped_wrapper(): void
    {
        $content = $this->renderPosIndex();

        $wrapperPos = strpos($content, 'max-h-[calc(100vh-12rem)]');
        $this->assertNotFalse($wrapperPos, 'Sidebar wrapper not found in rendered Blade.');

        foreach (self::PANEL_IDS as $panelId) {
            $panelPos = strpos($content, 'id="'.$panelId.'"');
            $this->assertNotFalse($panelPos, "Panel '{$panelId}' not found in rendered Blade.");
            $this->assertGreaterThan(
                $wrapperPos,
                $panelPos,
                "Panel '{$panelId}' is not positioned after the sidebar wrapper opening."
            );
        }
    }

    private function renderPosIndex(): string
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->seedMinimalScenario($user);

        return $this->get(route('pos.index'))->getContent();
    }

    /**
     * Slice from a panel's opening tag to the next panel's id (or end of
     * the rendered Blade). Used to assert that overflow-y-auto lives INSIDE
     * a given panel, not in some unrelated ancestor.
     */
    private function sliceFromPanelStartToNextMarker(string $content, string $panelId): string
    {
        $start = strpos($content, 'id="'.$panelId.'"');
        if ($start === false) {
            return '';
        }

        $end = strlen($content);
        foreach (self::PANEL_IDS as $otherId) {
            if ($otherId === $panelId) {
                continue;
            }
            $pos = strpos($content, 'id="'.$otherId.'"', $start + 1);
            if ($pos !== false && $pos < $end) {
                $end = $pos;
            }
        }

        return substr($content, $start, $end - $start);
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
