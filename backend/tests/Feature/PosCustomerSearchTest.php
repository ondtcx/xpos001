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
}
