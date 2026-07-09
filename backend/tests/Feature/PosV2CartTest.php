<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalePresentation;
use App\Models\SalePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PosV2CartTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_VIEW = __DIR__.'/../../resources/views/pos/index.blade.php';

    private const STORE_JS = __DIR__.'/../../resources/js/pos-store.js';

    #[Test]
    public function new_view_renders_venta_actual_header_with_cart_icon(): void
    {
        $this->renderNewView();

        $content = $this->get(route('pos.index'))->getContent();
        $this->assertStringContainsString('Venta actual', $content);
        $this->assertStringContainsString('itemsCount', $content);
    }

    #[Test]
    public function new_view_renders_currency_format_in_article_and_total(): void
    {
        $this->renderNewView();

        $content = $this->get(route('pos.index'))->getContent();
        // The view binds $store.posStore.formatMoney for the unit price and total
        $this->assertStringContainsString('$store.posStore.formatMoney', $content);
        $this->assertStringContainsString('formatMoney(p.precio)', $content);
    }

    #[Test]
    public function new_view_renders_decrement_and_increment_buttons_with_floor_at_1(): void
    {
        $this->renderNewView();

        $content = $this->get(route('pos.index'))->getContent();
        $this->assertStringContainsString('cambiarCantidad(item.id, Math.max(1, item.cantidad - 1))', $content);
        $this->assertStringContainsString('cambiarCantidad(item.id, item.cantidad + 1)', $content);
    }

    #[Test]
    public function new_view_renders_trash_button_that_calls_quitar(): void
    {
        $this->renderNewView();

        $content = $this->get(route('pos.index'))->getContent();
        $this->assertStringContainsString('@click="$store.posStore.quitar(item.id)"', $content);
    }

    #[Test]
    public function store_implements_decrement_floors_at_1_via_math_max(): void
    {
        $js = file_get_contents(self::STORE_JS);

        $this->assertStringContainsString(
            'cambiarCantidad(id, qty)',
            $js,
            'posStore must expose cambiarCantidad for qty controls.'
        );
        $this->assertStringContainsString(
            'Math.max(1, Math.floor(Number(qty) || 1))',
            $js,
            'posStore.cambiarCantidad must floor qty at 1.'
        );
    }

    #[Test]
    public function store_exposes_quitar_limpiar_and_anularVenta(): void
    {
        $js = file_get_contents(self::STORE_JS);

        $this->assertStringContainsString('quitar(id)', $js);
        $this->assertStringContainsString('limpiar()', $js);
        $this->assertStringContainsString('anularVenta()', $js);
        // Full reset semantics: cliente back to default, metodo back to efectivo, recibido empty.
        $this->assertMatchesRegularExpression(
            "/anularVenta\\(\\)\\s*{[^}]*items\\s*=\\s*\\[\\]/s",
            $js,
            'anularVenta must reset items to []'
        );
        $this->assertMatchesRegularExpression(
            "/anularVenta\\(\\)[\\s\\S]{0,400}metodo\\s*=\\s*'efectivo'/",
            $js,
            'anularVenta must reset metodo to efectivo'
        );
        $this->assertMatchesRegularExpression(
            "/anularVenta\\(\\)[\\s\\S]{0,400}recibido\\s*=\\s*''/",
            $js,
            'anularVenta must reset recibido to empty string'
        );
    }

    #[Test]
    public function store_format_money_implements_thousands_separator(): void
    {
        $js = file_get_contents(self::STORE_JS);

        $this->assertStringContainsString('formatMoney(value)', $js);
        // USD prefix, thousands separator via regex, 2 decimal places.
        $this->assertStringContainsString('USD', $js);
        $this->assertStringContainsString('toFixed(2)', $js);
        $this->assertStringContainsString('replace(/\\B(?=(\\d{3})+(?!\\d))/g, \',\')', $js);
    }

    private function renderNewView(): void
    {
        // Authenticate so the controller renders (not the login redirect).
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->seedMinimalScenario($user);
        // The new view refuses to render the layout when there's no default
        // customer. Seed one so the full markup is present.
        Customer::query()->create([
            'name' => 'Cliente General',
            'document' => '—',
            'is_default' => true,
            'is_active' => true,
        ]);

        // Set the feature flag so the controller renders the new view.
        config()->set('pos.enabled', true);
    }

    private function seedMinimalScenario(User $user): void
    {
        $category = Category::query()->firstOrCreate(['name' => 'Bebidas'], ['is_active' => true]);
        $brand = Brand::query()->firstOrCreate(['name' => 'Marca POS'], ['is_active' => true]);
        $baseUnit = BaseUnit::query()->firstOrCreate(['symbol' => 'u'], ['name' => 'Unidad']);
        $product = Product::query()->create([
            'name' => 'Coca-Cola 500 ml',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'internal_code' => 'POS-001',
            'status' => 'active',
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Unidad',
            'barcode' => '7861001000011',
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
            'price_amount' => 75,
            'min_price_amount' => 70,
            'starts_at' => now()->subDay(),
            'created_by' => $user->id,
            'reason' => 'Precio POS',
        ]);
        InventoryLot::query()->create([
            'variant_id' => $variant->id,
            'purchase_item_id' => null,
            'origin_type' => 'test',
            'origin_id' => 1,
            'received_at' => now()->subDay(),
            'expiration_date' => null,
            'initial_quantity' => 10,
            'available_quantity' => 10,
            'bonus_quantity' => 0,
            'unit_cost_final_amount' => 50,
            'suggested_sale_price_amount' => null,
            'is_estimated' => false,
            'status' => 'active',
        ]);
    }
}
