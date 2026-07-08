<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\SalePresentation;
use App\Models\SalePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PosFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_the_pos_page_with_simple_cash_focus(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->createPresentationScenario($user, 5);

        $response = $this->get(route('pos.index'));

        $response->assertOk();
        $response->assertSee('POS', false);
        $response->assertSee('Buscador principal', false);
        $response->assertSee('Cobrar efectivo', false);
        $response->assertSee('Cambiar método', false);
        $response->assertSee('Asignar cliente', false);
        $response->assertSee('Continuar en venta completa', false);
    }

    #[Test]
    public function it_renders_alpine_sidebar_state(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->createPresentationScenario($user, 5);

        $response = $this->get(route('pos.index'));

        $response->assertOk();
        $response->assertSee('x-data', false);
        $response->assertSee('activePanel', false);
        $response->assertSee('fiadoAutoEnabled', false);
        $response->assertSee('togglePanel', false);
        $response->assertSee('syncToHiddenInputs', false);
    }

    #[Test]
    public function it_renders_accordion_and_pin_state(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->createPresentationScenario($user, 5);

        $response = $this->get(route('pos.index'));

        $response->assertOk();
        $response->assertSee('pinnedPanels', false);
        $response->assertSee('togglePin', false);
        $response->assertSee("pinnedPanels.includes('customer')", false);
        $response->assertSee("pinnedPanels.includes('payment')", false);
    }

    #[Test]
    public function it_renders_pin_toggle_icons(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->createPresentationScenario($user, 5);

        $response = $this->get(route('pos.index'));

        $response->assertOk();
        $response->assertSee("togglePin('customer')", false);
        $response->assertSee("togglePin('payment')", false);
        $response->assertSee("togglePin('received')", false);
    }

    #[Test]
    public function it_creates_a_simple_cash_sale_from_pos(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $presentation = $this->createPresentationScenario($user, 10);
        $this->openCashSession($user);

        $response = $this->post(route('pos.store'), [
            'action' => 'checkout',
            'items' => [
                ['sale_presentation_id' => $presentation->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('pos.index'));
        $response->assertSessionHas('status');

        $sale = Sale::query()->first();

        $this->assertNotNull($sale);
        $this->assertSame(1000, $sale->total_amount);
        $this->assertSame(1000, $sale->paid_amount);
        $this->assertSame(0, $sale->credit_amount);
    }

    #[Test]
    public function it_allows_cash_sale_with_received_amount_for_change_support(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $presentation = $this->createPresentationScenario($user, 10);
        $this->openCashSession($user);

        $response = $this->post(route('pos.store'), [
            'action' => 'checkout',
            'payment_method' => 'cash',
            'received_amount' => 12.00,
            'items' => [
                ['sale_presentation_id' => $presentation->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('pos.index'));
        $this->assertDatabaseCount('sales', 1);
    }

    #[Test]
    public function it_rejects_cash_sale_when_received_amount_is_less_than_total(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $presentation = $this->createPresentationScenario($user, 10);
        $this->openCashSession($user);

        $response = $this->from(route('pos.index'))->post(route('pos.store'), [
            'action' => 'checkout',
            'payment_method' => 'cash',
            'received_amount' => 9.00,
            'items' => [
                ['sale_presentation_id' => $presentation->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('pos.index'));
        $response->assertSessionHasErrors('received_amount');
        $this->assertDatabaseCount('sales', 0);
    }

    #[Test]
    public function it_creates_credit_sale_from_pos_with_partial_cash_and_customer(): void
    {
        $user = User::factory()->create();
        $customer = \App\Models\Customer::query()->create(['name' => 'Cliente Fiado', 'is_active' => true]);
        $this->actingAs($user);

        $presentation = $this->createPresentationScenario($user, 10);
        $this->openCashSession($user);

        $response = $this->post(route('pos.store'), [
            'action' => 'checkout',
            'payment_method' => 'cash',
            'customer_id' => $customer->id,
            'received_amount' => 4.00,
            'allow_credit_sale' => 1,
            'confirm_credit_sale' => 1,
            'items' => [
                ['sale_presentation_id' => $presentation->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('pos.index'));

        $sale = Sale::query()->first();
        $receivable = Receivable::query()->first();

        $this->assertNotNull($sale);
        $this->assertSame(1000, $sale->total_amount);
        $this->assertSame(400, $sale->paid_amount);
        $this->assertSame(600, $sale->credit_amount);
        $this->assertNotNull($receivable);
        $this->assertSame(600, $receivable->pending_amount);
    }

    #[Test]
    public function it_creates_full_credit_sale_from_pos_without_received_amount(): void
    {
        $user = User::factory()->create();
        $customer = \App\Models\Customer::query()->create(['name' => 'Cliente Fiado Total', 'is_active' => true]);
        $this->actingAs($user);

        $presentation = $this->createPresentationScenario($user, 10);
        $this->openCashSession($user);

        $response = $this->post(route('pos.store'), [
            'action' => 'checkout',
            'payment_method' => 'cash',
            'customer_id' => $customer->id,
            'allow_credit_sale' => 1,
            'confirm_credit_sale' => 1,
            'items' => [
                ['sale_presentation_id' => $presentation->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('pos.index'));

        $sale = Sale::query()->first();
        $receivable = Receivable::query()->first();

        $this->assertNotNull($sale);
        $this->assertSame(0, $sale->paid_amount);
        $this->assertSame(1000, $sale->credit_amount);
        $this->assertNotNull($receivable);
        $this->assertSame(1000, $receivable->pending_amount);
    }

    #[Test]
    public function it_requires_customer_before_credit_sale_from_pos(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $presentation = $this->createPresentationScenario($user, 10);
        $this->openCashSession($user);

        $response = $this->from(route('pos.index'))->post(route('pos.store'), [
            'action' => 'checkout',
            'payment_method' => 'cash',
            'received_amount' => 4.00,
            'allow_credit_sale' => 1,
            'confirm_credit_sale' => 1,
            'items' => [
                ['sale_presentation_id' => $presentation->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('pos.index'));
        $response->assertSessionHasErrors('customer_id');
        $this->assertDatabaseCount('sales', 0);
    }

    #[Test]
    public function it_requires_explicit_confirmation_before_credit_sale_from_pos(): void
    {
        $user = User::factory()->create();
        $customer = \App\Models\Customer::query()->create(['name' => 'Cliente Fiado', 'is_active' => true]);
        $this->actingAs($user);

        $presentation = $this->createPresentationScenario($user, 10);
        $this->openCashSession($user);

        $response = $this->from(route('pos.index'))->post(route('pos.store'), [
            'action' => 'checkout',
            'payment_method' => 'cash',
            'customer_id' => $customer->id,
            'received_amount' => 4.00,
            'allow_credit_sale' => 1,
            'items' => [
                ['sale_presentation_id' => $presentation->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('pos.index'));
        $response->assertSessionHasErrors('credit_sale');
        $this->assertDatabaseCount('sales', 0);
    }

    #[Test]
    public function it_creates_a_simple_transfer_sale_from_pos(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $presentation = $this->createPresentationScenario($user, 10);
        $this->openCashSession($user);

        $response = $this->post(route('pos.store'), [
            'action' => 'checkout',
            'payment_method' => 'transfer',
            'items' => [
                ['sale_presentation_id' => $presentation->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('pos.index'));

        $sale = Sale::query()->first();

        $this->assertNotNull($sale);
        $this->assertSame(1000, $sale->total_amount);
        $this->assertSame(1000, $sale->paid_amount);
        $this->assertDatabaseHas('sale_payments', [
            'sale_id' => $sale->id,
            'payment_method' => 'transfer',
            'amount' => 1000,
        ]);
    }

    #[Test]
    public function it_creates_a_mixed_payment_sale_from_pos(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $presentation = $this->createPresentationScenario($user, 10);
        $this->openCashSession($user);

        $response = $this->post(route('pos.store'), [
            'action' => 'checkout',
            'payment_method' => 'mixed',
            'mixed_payments' => [
                'cash' => 4.00,
                'transfer' => 6.00,
            ],
            'items' => [
                ['sale_presentation_id' => $presentation->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('pos.index'));

        $sale = Sale::query()->first();

        $this->assertNotNull($sale);
        $this->assertSame(1000, $sale->total_amount);
        $this->assertSame(1000, $sale->paid_amount);
        $this->assertDatabaseHas('sale_payments', [
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'amount' => 400,
        ]);
        $this->assertDatabaseHas('sale_payments', [
            'sale_id' => $sale->id,
            'payment_method' => 'transfer',
            'amount' => 600,
        ]);
    }

    #[Test]
    public function it_rejects_mixed_payments_that_do_not_match_total(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $presentation = $this->createPresentationScenario($user, 10);
        $this->openCashSession($user);

        $response = $this->from(route('pos.index'))->post(route('pos.store'), [
            'action' => 'checkout',
            'payment_method' => 'mixed',
            'mixed_payments' => [
                'cash' => 3.00,
                'transfer' => 6.00,
            ],
            'items' => [
                ['sale_presentation_id' => $presentation->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('pos.index'));
        $response->assertSessionHasErrors('mixed_payments');
        $this->assertDatabaseCount('sales', 0);
    }

    #[Test]
    public function it_still_requires_open_cash_session_for_transfer_sales_from_pos(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $presentation = $this->createPresentationScenario($user, 10);

        $response = $this->from(route('pos.index'))->post(route('pos.store'), [
            'action' => 'checkout',
            'payment_method' => 'transfer',
            'items' => [
                ['sale_presentation_id' => $presentation->id, 'quantity' => 1],
            ],
        ]);

        $response->assertRedirect(route('pos.index'));
        $response->assertSessionHasErrors('pos');
        $this->assertDatabaseCount('sales', 0);
    }

    #[Test]
    public function it_derives_stock_warning_cases_to_full_sale_with_context(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $presentation = $this->createPresentationScenario($user, 0);

        $response = $this->from(route('pos.index'))->post(route('pos.store'), [
            'action' => 'checkout',
            'payment_method' => 'cash',
            'items' => [
                ['sale_presentation_id' => $presentation->id, 'quantity' => 1],
            ],
        ]);

        $response->assertRedirect(route('sales.create'));
        $response->assertSessionHas('status');
        $response->assertSessionHasInput('items.0.sale_presentation_id', $presentation->id);
        $response->assertSessionHasInput('items.0.quantity', 1.0);
    }

    #[Test]
    public function it_allows_explicit_transition_to_full_sale_from_pos(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $presentation = $this->createPresentationScenario($user, 3);

        $response = $this->from(route('pos.index'))->post(route('pos.store'), [
            'action' => 'complete',
            'payment_method' => 'transfer',
            'items' => [
                ['sale_presentation_id' => $presentation->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('sales.create'));
        $response->assertSessionHasInput('items.0.sale_presentation_id', $presentation->id);
        $response->assertSessionHasInput('items.0.quantity', 2.0);
    }

    private function createPresentationScenario(User $user, float $availableQuantity): SalePresentation
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

        if ($availableQuantity > 0) {
            InventoryLot::query()->create([
                'variant_id' => $variant->id,
                'purchase_item_id' => null,
                'origin_type' => 'test',
                'origin_id' => 1,
                'received_at' => now()->subDay(),
                'expiration_date' => null,
                'initial_quantity' => $availableQuantity,
                'available_quantity' => $availableQuantity,
                'bonus_quantity' => 0,
                'unit_cost_final_amount' => 300,
                'suggested_sale_price_amount' => null,
                'is_estimated' => false,
                'status' => 'active',
            ]);
        }

        return $presentation;
    }

    private function openCashSession(User $user): void
    {
        CashSession::query()->create([
            'opened_by' => $user->id,
            'opened_at' => now()->subHour(),
            'opening_amount' => 1000,
            'status' => 'open',
        ]);
    }
}
