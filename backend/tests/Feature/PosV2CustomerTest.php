<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PosV2CustomerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function exactly_one_default_customer_can_exist_when_only_one_is_marked(): void
    {
        Customer::query()->create(['name' => 'Cliente General', 'document' => '—', 'is_default' => true, 'is_active' => true]);
        Customer::query()->create(['name' => 'María Gómez', 'document' => '0912345678', 'is_default' => false, 'is_active' => true]);
        Customer::query()->create(['name' => 'José Paredes', 'document' => '0923456789', 'is_default' => false, 'is_active' => true]);

        $this->assertSame(1, Customer::query()->where('is_default', true)->count());
    }

    #[Test]
    public function customer_default_scope_returns_the_is_default_row(): void
    {
        $general = Customer::query()->create([
            'name' => 'Cliente General',
            'document' => '—',
            'is_default' => true,
            'is_active' => true,
        ]);
        Customer::query()->create(['name' => 'María Gómez', 'document' => '0912345678', 'is_default' => false, 'is_active' => true]);

        $result = Customer::default()->first();

        $this->assertNotNull($result);
        $this->assertSame($general->id, $result->id);
        $this->assertSame('Cliente General', $result->name);
        $this->assertSame('—', $result->document);
    }

    #[Test]
    public function is_default_attribute_is_cast_to_boolean(): void
    {
        $general = Customer::query()->create([
            'name' => 'Cliente General',
            'document' => '—',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->assertIsBool($general->is_default);
        $this->assertTrue($general->is_default);
    }

    #[Test]
    public function dropdown_options_include_debt_for_customers_with_open_receivables(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $maria = Customer::query()->create([
            'name' => 'María Gómez',
            'document' => '0912345678',
            'is_active' => true,
            'is_default' => false,
        ]);

        $sale = \App\Models\Sale::query()->create([
            'sold_at' => now()->subDay(),
            'subtotal_amount' => 1234,
            'discount_amount' => 0,
            'total_amount' => 1234,
            'paid_amount' => 0,
            'credit_amount' => 1234,
            'status' => \App\Models\Sale::STATUS_CONFIRMED,
            'created_by' => $user->id,
        ]);

        Receivable::query()->create([
            'customer_id' => $maria->id,
            'sale_id' => $sale->id,
            'original_amount' => 1234,
            'pending_amount' => 1234,
            'opened_at' => now()->subDay(),
            'status' => 'open',
        ]);

        $response = $this->getJson(route('pos.customers.search', ['q' => 'María']));

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'María Gómez',
            'saldo_fiado' => 12.34,
        ]);
        $response->assertJsonFragment(['document' => '0912345678']);
    }

    #[Test]
    public function inline_search_filters_by_document(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Customer::query()->create([
            'name' => 'Cliente General',
            'document' => '—',
            'is_default' => true,
            'is_active' => true,
        ]);
        Customer::query()->create([
            'name' => 'María Gómez',
            'document' => '0912345678',
            'is_active' => true,
            'is_default' => false,
        ]);

        $response = $this->getJson(route('pos.customers.search', ['q' => '—']));

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Cliente General']);
    }

    #[Test]
    public function search_endpoint_can_filter_by_document(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Customer::query()->create(['name' => 'Cliente General', 'document' => '—', 'is_default' => true, 'is_active' => true]);
        Customer::query()->create(['name' => 'María Gómez', 'document' => '0912345678', 'is_active' => true, 'is_default' => false]);

        $response = $this->getJson(route('pos.customers.search', ['q' => '0912345678']));

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'María Gómez']);
        $response->assertJsonCount(1, 'results');
    }

    #[Test]
    public function customers_without_debt_have_zero_saldo_fiado(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Customer::query()->create(['name' => 'José Paredes', 'document' => '0923456789', 'is_active' => true, 'is_default' => false]);

        $response = $this->getJson(route('pos.customers.search', ['q' => 'José']));

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'José Paredes', 'saldo_fiado' => 0.0]);
    }

    #[Test]
    public function pos_refuses_render_without_default_customer(): void
    {
        // New v2 view (enabled by default in PR 3) surfaces a missing-default guard.
        config()->set('pos.enabled', true);

        Customer::query()->create(['name' => 'María Gómez', 'document' => '0912345678', 'is_active' => true, 'is_default' => false]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('pos.index'));

        $response->assertOk();
        $response->assertSee('Falta el cliente por defecto', false);
    }

    #[Test]
    public function dropdown_renders_with_inline_search_and_keyboard_navigation(): void
    {
        // Feature flag ON so the controller renders the new v2 view (in PR 3
        // the flag defaults to true; we set it explicitly so this test pins
        // the wiring without depending on default value).
        config()->set('pos.enabled', true);

        $user = User::factory()->create();
        $this->actingAs($user);

        Customer::query()->create(['name' => 'Cliente General', 'document' => '—', 'is_default' => true, 'is_active' => true]);
        Customer::query()->create(['name' => 'María Gómez', 'document' => '0912345678', 'is_default' => false, 'is_active' => true]);

        $response = $this->get(route('pos.index'));

        $response->assertOk();
        $content = $response->getContent();

        // The new view exposes keyboard nav bindings on the customer dropdown input.
        $this->assertStringContainsString('@keydown.escape.prevent="$store.posStore.clienteOpen = false"', $content);
        $this->assertStringContainsString('@keydown.arrow-down.prevent="$store.posStore.moveClienteHighlight(1)"', $content);
        $this->assertStringContainsString('@keydown.arrow-up.prevent="$store.posStore.moveClienteHighlight(-1)"', $content);
        $this->assertStringContainsString('@keydown.enter.prevent="if ($store.posStore.clienteHighlight >= 0) { $store.posStore.setCliente($store.posStore.filteredClientes[$store.posStore.clienteHighlight]); }"', $content);
    }

    #[Test]
    public function store_implements_keyboard_highlight_helper(): void
    {
        $store = file_get_contents(__DIR__.'/../../resources/js/pos-store.js');

        $this->assertStringContainsString('moveClienteHighlight(delta)', $store);
    }

    #[Test]
    public function selection_of_cliente_general_resets_fiado_to_efectivo(): void
    {
        $store = file_get_contents(__DIR__.'/../../resources/js/pos-store.js');

        // When the user picks Cliente General while metodo === 'fiado', the
        // store must reset metodo to 'efectivo' (Fiado is disabled for General).
        $this->assertMatchesRegularExpression(
            "/setCliente\\([\\s\\S]{0,500}if \\(this\\.metodo === 'fiado' && customer\\.id === this\\.generalId\\) \\{[\\s\\S]{0,200}this\\.metodo = 'efectivo'/",
            $store,
            'setCliente must reset metodo to efectivo when picking General while in Fiado'
        );
    }
}
