<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_dashboard_page_loads_successfully(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
    }

    #[Test]
    public function the_status_card_is_visible(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertSee('Estado del producto', false);
    }

    #[Test]
    public function the_operations_grid_renders_all_four_links(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertSee(route('sales.index'), false);
        $response->assertSee(route('purchases.index'), false);
        $response->assertSee(route('cash.index'), false);
        $response->assertSee(route('reports.index'), false);
    }

    #[Test]
    public function the_catalog_grid_renders_all_four_links(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertSee(route('categories.index'), false);
        $response->assertSee(route('brands.index'), false);
        $response->assertSee(route('base-units.index'), false);
        $response->assertSee(route('products.index'), false);
    }

    #[Test]
    public function the_next_focus_panel_is_visible(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertSee('Siguiente foco recomendado', false);
    }

    #[Test]
    public function the_quick_links_panel_is_visible(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertSee('Accesos rápidos', false);
        $response->assertSee(route('pos.index'), false);
        $response->assertSee(route('customers.index'), false);
        $response->assertSee(route('inventory-lots.index'), false);
    }

    #[Test]
    public function it_renders_with_empty_database(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Estado del producto', false);
        $response->assertSee('Siguiente foco recomendado', false);
        $response->assertSee('Accesos rápidos', false);
    }

    #[Test]
    public function it_does_not_use_legacy_indigo_color(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        // Check dashboard primary surfaces no longer use indigo.
        // Layout navigation (nav-link, responsive-nav-link — sidebar workstream,
        // out of scope) still renders border-indigo-400 etc., so we check the
        // specific patterns that were previously in the dashboard content.
        $response->assertDontSee('hover:border-indigo-300', false);
        $response->assertDontSee('hover:ring-indigo-300', false);
        $response->assertDontSee('hover:bg-indigo-50/30', false);
    }
}
