<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PosCustomerSearchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function search_by_name_returns_matching_customers(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Customer::query()->create(['name' => 'John Doe', 'is_active' => true]);
        Customer::query()->create(['name' => 'Johanna Smith', 'is_active' => true]);
        Customer::query()->create(['name' => 'Alice Wonderland', 'is_active' => true]);

        $response = $this->getJson(route('pos.customers.search', ['q' => 'Joh']));

        $response->assertOk();
        $response->assertJsonCount(2, 'results');
        $response->assertJsonPath('results.0.name', 'John Doe');
        $response->assertJsonPath('results.1.name', 'Johanna Smith');
    }

    #[Test]
    public function search_by_phone_returns_matching_customers(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Customer::query()->create(['name' => 'Carlos López', 'phone' => '555-1234', 'is_active' => true]);
        Customer::query()->create(['name' => 'María García', 'phone' => '555-5678', 'is_active' => true]);
        Customer::query()->create(['name' => 'Pedro Pérez', 'phone' => '999-0000', 'is_active' => true]);

        $response = $this->getJson(route('pos.customers.search', ['q' => '555']));

        $response->assertOk();
        $response->assertJsonCount(2, 'results');
        $response->assertJsonPath('results.0.name', 'Carlos López');
        $response->assertJsonPath('results.1.name', 'María García');
    }

    #[Test]
    public function empty_query_returns_empty_results(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Customer::query()->create(['name' => 'John Doe', 'is_active' => true]);

        $response = $this->getJson(route('pos.customers.search', ['q' => '']));

        $response->assertOk();
        $response->assertJsonCount(0, 'results');
    }

    #[Test]
    public function search_only_returns_active_customers(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Customer::query()->create(['name' => 'Active Client', 'is_active' => true]);
        Customer::query()->create(['name' => 'Inactive Client', 'is_active' => false]);

        $response = $this->getJson(route('pos.customers.search', ['q' => 'Client']));

        $response->assertOk();
        $response->assertJsonCount(1, 'results');
        $response->assertJsonPath('results.0.name', 'Active Client');
    }

    #[Test]
    public function non_authenticated_request_redirects_to_login(): void
    {
        $response = $this->getJson(route('pos.customers.search', ['q' => 'John']));

        $response->assertUnauthorized();
    }

    #[Test]
    public function search_limits_to_ten_results(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        for ($i = 1; $i <= 12; $i++) {
            Customer::query()->create(['name' => "Test Client $i", 'is_active' => true]);
        }

        $response = $this->getJson(route('pos.customers.search', ['q' => 'Test']));

        $response->assertOk();
        $response->assertJsonCount(10, 'results');
    }

    #[Test]
    public function blade_typeahead_input_uses_store_and_debounce(): void
    {
        // PR 3 — typeahead Blade binding. The input inside the "Asignar cliente"
        // panel must read its value from the Alpine store and trigger
        // `searchCustomers()` after a 300ms debounce. This is the wiring that
        // PR 1a left out and PR 3 owns.
        $blade = file_get_contents(__DIR__.'/../../resources/views/pos/index.blade.php');

        $this->assertStringContainsString(
            'x-model="$store.posSidebar.customerQuery"',
            $blade,
            'The customer typeahead input must be bound to $store.posSidebar.customerQuery via x-model.'
        );
        $this->assertStringContainsString(
            '@input.debounce.300ms="$store.posSidebar.searchCustomers()"',
            $blade,
            'The customer typeahead input must debounce 300ms before calling searchCustomers().'
        );
    }

    #[Test]
    public function blade_has_no_quick_create_affordance(): void
    {
        // Q2 is closed: high-velocity customer creation is OUT of scope for
        // pos-ux-refinements. The typeahead dropdown MUST NOT expose a button,
        // link, or hint for creating a new client. This snapshot guards the
        // invariant — if anyone adds "Crear cliente nuevo" back, this test
        // fails immediately.
        $blade = file_get_contents(__DIR__.'/../../resources/views/pos/index.blade.php');

        $markers = ['crear cliente', 'alta rápida', 'nuevo cliente'];

        foreach ($markers as $marker) {
            $this->assertStringNotContainsString(
                $marker,
                $blade,
                "The POS view must not contain the quick-create marker '{$marker}' (Q2 out of scope)."
            );
        }
    }

    #[Test]
    public function store_exposes_search_and_select_methods(): void
    {
        // Sanity check that the typeahead methods added in PR 1a's scope creep
        // are still present in the store. PR 3's contract is that these
        // methods exist and are wired to the Blade; if any is missing the
        // typeahead cannot work.
        $store = file_get_contents(__DIR__.'/../../resources/js/pos-sidebar-store.js');

        $this->assertStringContainsString('searchCustomers()', $store);
        $this->assertStringContainsString('selectCustomer(customer)', $store);
        $this->assertStringContainsString('clearCustomer()', $store);
    }
}
