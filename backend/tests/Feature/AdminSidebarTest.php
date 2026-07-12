<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminSidebarTest extends TestCase
{
    use RefreshDatabase;

    private const NAV_ROUTES = [
        'dashboard',
        'categories.index',
        'brands.index',
        'base-units.index',
        'products.index',
        'suppliers.index',
        'purchases.index',
        'opening-inventory.index',
        'customers.index',
        'sales.index',
        'cash.index',
        'reports.index',
    ];

    #[Test]
    public function sidebar_renders_with_brand_and_twelve_anchors(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertOk();

        // REQ-1: Sidebar aside is present with fixed positioning and w-64
        $response->assertSee('<aside', false);
        $response->assertSee('w-64', false);

        // REQ-1: Main wrapper has content offset
        $response->assertSee('md:pl-64', false);

        // REQ-1: All 12 nav routes are present as links in the sidebar
        foreach (self::NAV_ROUTES as $route) {
            $response->assertSee(route($route), false);
        }
    }

    #[Test]
    public function pos_routes_do_not_render_admin_sidebar(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('pos.index'));

        $response->assertOk();

        // REQ-7: POS must NOT contain the admin sidebar aside
        $response->assertDontSee('<aside', false);

        // REQ-7: POS must NOT have the sidebar width class
        $response->assertDontSee('w-64', false);

        // REQ-7: POS must NOT have the main content offset that comes with the sidebar
        $response->assertDontSee('md:pl-64', false);
    }

    #[Test]
    public function active_route_highlighting_for_categories(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('categories.index'));
        $response->assertOk();

        // REQ-2: The active link MUST show bg-emerald-50 text-emerald-700
        $response->assertSee('bg-emerald-50 text-emerald-700', false);

        // REQ-2: Exactly one link should be active (no false positives)
        $this->assertEquals(
            1,
            substr_count($response->content(), 'bg-emerald-50 text-emerald-700'),
            'Expected exactly one active link with bg-emerald-50 text-emerald-700',
        );

        // REQ-2: The active link should be the Categorías link
        // The label "Categorías" appears right after the active class in the DOM
        $response->assertSeeText('Categorías');
    }

    #[Test]
    public function active_route_highlighting_for_opening_inventory(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('opening-inventory.index'));
        $response->assertOk();

        // REQ-2: The active link MUST show bg-emerald-50 text-emerald-700
        $response->assertSee('bg-emerald-50 text-emerald-700', false);

        // REQ-2: Exactly one link should be active
        $this->assertEquals(
            1,
            substr_count($response->content(), 'bg-emerald-50 text-emerald-700'),
            'Expected exactly one active link with bg-emerald-50 text-emerald-700',
        );

        // REQ-2: The active link should be the Inventario link
        $response->assertSeeText('Inventario');
    }

    #[Test]
    public function active_route_highlighting_for_inventory_lots(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('inventory-lots.index'));
        $response->assertOk();

        // REQ-2: The active link MUST show bg-emerald-50 text-emerald-700
        $response->assertSee('bg-emerald-50 text-emerald-700', false);

        // REQ-2: Exactly one link should be active
        $this->assertEquals(
            1,
            substr_count($response->content(), 'bg-emerald-50 text-emerald-700'),
            'Expected exactly one active link with bg-emerald-50 text-emerald-700',
        );

        // REQ-2: The active link should be the Inventario link
        // (inventory-lots.* pattern is mapped to the same nav item)
        $response->assertSeeText('Inventario');
    }

    #[Test]
    public function heroicons_present_on_every_nav_link(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();

        // REQ-3: Each of the 12 nav links MUST contain an inline SVG with viewBox
        // Count all occurrences of viewBox="0 0 24 24" (one per icon)
        $svgCount = substr_count($response->content(), 'viewBox="0 0 24 24"');
        $this->assertEquals(12, $svgCount, 'Expected exactly 12 heroicon SVGs (one per nav link)');

        // Verify they are all outline-style Heroicons
        $response->assertSee('stroke="currentColor"', false);
        $response->assertSee('stroke-width="1.5"', false);
    }
}
