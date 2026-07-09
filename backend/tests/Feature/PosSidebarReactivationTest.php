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
 * PR 2 — Reactivación de paneles (usedPanels + used class binding).
 *
 * Covers the ADDED requirement "Panel Reactivation" in the modified
 * pos-sidebar-state delta spec, and the pos-panel-reactivation spec.
 * The store must:
 *  - keep `usedPanels: []` as the initial state (PR 1a foundation)
 *  - expose a `markUsed(name)` action that is idempotent
 *  - call `markUsed(name)` from `togglePanel` in the OPEN branch only
 *  - replace the `isButtonUsed(name)` stub with a real implementation
 *    that reads `usedPanels`
 *
 * The four contextual buttons must render a `used` class binding
 * resolved from `$store.posSidebar.isButtonUsed('<name>')`.
 */
class PosSidebarReactivationTest extends TestCase
{
    use RefreshDatabase;

    private const STORE_JS = __DIR__.'/../../resources/js/pos-sidebar-store.js';

    #[Test]
    public function initial_state_exposes_empty_used_panels(): void
    {
        $store = file_get_contents(self::STORE_JS);

        $this->assertStringContainsString('usedPanels: []', $store);
    }

    #[Test]
    public function store_exposes_mark_used_action_that_is_idempotent(): void
    {
        $store = file_get_contents(self::STORE_JS);

        $this->assertStringContainsString('markUsed(name)', $store);

        // markUsed must guard against duplicates — the canonical pattern is
        // `if (!this.usedPanels.includes(name))` before pushing.
        $body = $this->extractFunctionBody($store, 'markUsed(name)');
        $this->assertNotNull($body, 'markUsed function body not found in store.');
        $this->assertStringContainsString(
            'usedPanels.includes(name)',
            $body,
            'markUsed must use usedPanels.includes(name) to ensure idempotency.'
        );
    }

    #[Test]
    public function toggle_panel_invokes_mark_used_only_on_open(): void
    {
        $store = file_get_contents(self::STORE_JS);

        $this->assertStringContainsString('togglePanel(name)', $store);
        $this->assertStringContainsString('this.markUsed(name)', $store);

        $body = $this->extractFunctionBody($store, 'togglePanel(name)');
        $this->assertNotNull($body, 'togglePanel function body not found in store.');

        // Split at the close-branch `return;` that ends the
        // `if (this.activePanel === name) { ... return; }` block.
        $parts = preg_split('/\breturn;\s*\n/', $body, 2);
        $closeBranch = $parts[0] ?? '';
        $openBranch = $parts[1] ?? '';

        $this->assertStringNotContainsString(
            'markUsed',
            $closeBranch,
            'markUsed must NOT be invoked in the close branch of togglePanel.'
        );
        $this->assertStringContainsString(
            'markUsed',
            $openBranch,
            'markUsed must be invoked in the open branch of togglePanel.'
        );
    }

    #[Test]
    public function is_button_used_returns_true_when_panel_is_in_used_panels(): void
    {
        $store = file_get_contents(self::STORE_JS);

        $body = $this->extractFunctionBody($store, 'isButtonUsed(name)');
        $this->assertNotNull($body, 'isButtonUsed function body not found in store.');

        // The real implementation must consult usedPanels, not return a stub.
        $this->assertStringContainsString(
            'usedPanels.includes(name)',
            $body,
            'isButtonUsed must read from usedPanels.includes(name).'
        );
        $this->assertStringNotContainsString(
            'return false',
            $body,
            'isButtonUsed is no longer allowed to be a stub returning false.'
        );
    }

    #[Test]
    public function all_four_contextual_buttons_render_used_class_binding(): void
    {
        $content = $this->renderPosIndex();

        foreach (['customer', 'payment', 'received', 'credit'] as $name) {
            $this->assertStringContainsString(
                "\$store.posSidebar.isButtonUsed('{$name}')",
                $content,
                "Button '{$name}' is missing the isButtonUsed binding in its :class."
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

    /**
     * Extract the body of a function by name. Brace-balanced scan
     * to handle nested objects/blocks correctly. Returns null if not found.
     */
    private function extractFunctionBody(string $source, string $signature): ?string
    {
        $pos = strpos($source, $signature);
        if ($pos === false) {
            return null;
        }

        $openBrace = strpos($source, '{', $pos);
        if ($openBrace === false) {
            return null;
        }

        $depth = 0;
        $length = strlen($source);
        for ($i = $openBrace; $i < $length; $i++) {
            $char = $source[$i];
            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($source, $openBrace + 1, $i - $openBrace - 1);
                }
            }
        }

        return null;
    }
}
