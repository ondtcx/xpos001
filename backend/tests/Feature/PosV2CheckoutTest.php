<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SalePresentation;
use App\Models\SalePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PosV2CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_VIEW = __DIR__.'/../../resources/views/pos/index.blade.php';

    private const STORE_JS = __DIR__.'/../../resources/js/pos-store.js';

    private const APP_JS = __DIR__.'/../../resources/js/app.js';

    #[Test]
    public function new_view_renders_three_tabs_no_tarjeta(): void
    {
        $this->renderNewView();
        $content = $this->get(route('pos.index'))->getContent();

        $this->assertStringContainsString('Efectivo', $content);
        $this->assertStringContainsString('Transfer.', $content);
        $this->assertStringContainsString('Fiado', $content);
        $this->assertStringNotContainsString('>Tarjeta<', $content);
    }

    #[Test]
    public function new_view_renders_efectivo_input_and_quick_chips(): void
    {
        $this->renderNewView();
        $content = $this->get(route('pos.index'))->getContent();

        $this->assertStringContainsString('Efectivo recibido', $content);
        $this->assertStringContainsString('USD 20', $content);
        $this->assertStringContainsString('USD 50', $content);
        $this->assertStringContainsString('USD 100', $content);
        $this->assertStringContainsString('Vuelto:', $content);
    }

    #[Test]
    public function new_view_hides_cash_ui_when_metodo_is_transfer(): void
    {
        $this->renderNewView();
        $content = $this->get(route('pos.index'))->getContent();

        // The efectivo block has x-show="$store.posStore.metodo === 'efectivo'"
        $this->assertStringContainsString("x-show=\"\$store.posStore.metodo === 'efectivo'\"", $content);
    }

    #[Test]
    public function new_view_disables_fiado_for_cliente_general_in_view(): void
    {
        $this->renderNewView();
        $content = $this->get(route('pos.index'))->getContent();

        $this->assertStringContainsString(':disabled="$store.posStore.cliente?.id === $store.posStore.generalId"', $content);
    }

    #[Test]
    public function new_view_displays_totals_and_subtotal_label(): void
    {
        $this->renderNewView();
        $content = $this->get(route('pos.index'))->getContent();

        $this->assertStringContainsString('Subtotal (', $content);
        $this->assertStringContainsString('art.)', $content);
        $this->assertStringContainsString('Total', $content);
    }

    #[Test]
    public function new_view_cobrar_button_is_disabled_when_empty(): void
    {
        $this->renderNewView();
        $content = $this->get(route('pos.index'))->getContent();

        $this->assertStringContainsString(':disabled="!$store.posStore.puedeCobrar || $store.posStore.procesando"', $content);
    }

    #[Test]
    public function ajax_cobrar_returns_json_success_with_message(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $general = Customer::query()->create([
            'name' => 'Cliente General',
            'document' => '—',
            'is_default' => true,
            'is_active' => true,
        ]);
        $presentation = $this->createPresentationScenario($user);

        $response = $this->postJson(route('pos.store'), [
            'metodo' => 'efectivo',
            'customer_id' => $general->id,
            'received_amount' => 10.00,
            'items' => [
                ['sale_presentation_id' => $presentation->id, 'quantity' => 2],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['ok', 'sale_id', 'message']);
        $response->assertJsonFragment(['ok' => true]);

        $sale = Sale::query()->first();
        $this->assertNotNull($sale);
        $this->assertSame(1000, $sale->total_amount);
    }

    #[Test]
    public function ajax_cobrar_with_fiado_metodo_creates_sale_and_receivable(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $general = Customer::query()->create([
            'name' => 'Cliente General',
            'document' => '—',
            'is_default' => true,
            'is_active' => true,
        ]);
        $maria = Customer::query()->create([
            'name' => 'María Gómez',
            'document' => '0912345678',
            'is_active' => true,
            'is_default' => false,
        ]);
        $presentation = $this->createPresentationScenario($user);

        $response = $this->postJson(route('pos.store'), [
            'metodo' => 'fiado',
            'customer_id' => $maria->id,
            'received_amount' => 0.0,
            'items' => [
                ['sale_presentation_id' => $presentation->id, 'quantity' => 2],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['ok' => true]);

        $sale = Sale::query()->first();
        $this->assertNotNull($sale);
        $this->assertSame(1000, $sale->total_amount);
        $this->assertSame(1000, $sale->credit_amount);

        $this->assertDatabaseHas('receivables', [
            'customer_id' => $maria->id,
            'sale_id' => $sale->id,
            'status' => 'open',
        ]);
    }

    #[Test]
    public function ajax_cobrar_validation_failure_returns_422_with_errors(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('pos.store'), [
            'metodo' => 'efectivo',
            'items' => [], // empty items triggers validation
        ]);

        $response->assertStatus(422);
        // FormRequest renders 422 with `message` and `errors` (Laravel default).
        // The posStore JavaScript reads response.errors[first key] and shows
        // it as a red toast — preserved by Laravel's default shape.
        $response->assertJsonStructure(['errors']);
        $this->assertNotEmpty($response->json('errors'));
    }

    #[Test]
    public function app_js_registers_posStore(): void
    {
        $appJs = file_get_contents(self::APP_JS);

        $this->assertStringContainsString('registerPosStore', $appJs);
        $this->assertStringContainsString('__POS_INITIAL_V2__', $appJs);
        $this->assertStringContainsString('Alpine.start', $appJs);
    }

    private function renderNewView(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::query()->firstOrCreate(['name' => 'Bebidas'], ['is_active' => true]);
        $brand = Brand::query()->firstOrCreate(['name' => 'Marca POS'], ['is_active' => true]);
        $baseUnit = BaseUnit::query()->firstOrCreate(['symbol' => 'u'], ['name' => 'Unidad']);
        Customer::query()->create(['name' => 'Cliente General', 'document' => '—', 'is_default' => true, 'is_active' => true]);

        config()->set('pos.enabled', true);
    }

    private function createPresentationScenario(User $user): SalePresentation
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
            'price_amount' => 500,
            'min_price_amount' => 400,
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
            'unit_cost_final_amount' => 300,
            'suggested_sale_price_amount' => null,
            'is_estimated' => false,
            'status' => 'active',
        ]);

        return $presentation;
    }
}
