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

        // REQ-2: Exactly one link should be active
        $this->assertEquals(
            1,
            substr_count($response->content(), 'bg-emerald-50 text-emerald-700'),
            'Expected exactly one active link with bg-emerald-50 text-emerald-700',
        );

        // REQ-2: The active link should be the Categorias link
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
    public function primary_button_uses_emerald_accent(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // REQ-6: GET a page that has a primary action button
        // Customers index has "Nuevo cliente" with bg-emerald-600
        $response = $this->get(route('customers.index'));
        $response->assertOk();

        // The primary action button MUST use emerald accent
        $response->assertSee('bg-emerald-600', false);

        // The primary action button must NOT use indigo accent
        $response->assertDontSee('bg-indigo-600', false);
    }

    #[Test]
    public function heroicons_present_on_every_nav_link(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();

        // REQ-3: Each of the 12 nav links MUST contain an inline SVG with viewBox
        // plus 2 sidebar UI SVGs (hamburger toggle + close button) = 14 total
        // Count all occurrences of viewBox="0 0 24 24" (one per icon)
        $svgCount = substr_count($response->content(), 'viewBox="0 0 24 24"');
        $this->assertEquals(14, $svgCount, 'Expected exactly 14 heroicon SVGs (12 nav links + 2 sidebar UI icons)');

        // Verify they are all outline-style Heroicons
        $response->assertSee('stroke="currentColor"', false);
        $response->assertSee('stroke-width="1.5"', false);
    }

    #[Test]
    public function user_block_renders_with_initials_and_logout(): void
    {
        $user = User::factory()->create([
            'name' => 'Diego Paz',
            'email' => 'diego@test.com',
        ]);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();

        // REQ-8: User name and email appear in sidebar block
        $response->assertSeeText('Diego Paz');
        $response->assertSeeText('diego@test.com');

        // REQ-8: Initials derived from first letter of first and last name
        $response->assertSee('DP', false);

        // REQ-8: Logout form present with POST method to logout route
        $response->assertSee('<form method="POST"', false);
        $response->assertSee(route('logout'), false);
        $response->assertSeeText('Log out');
    }

    #[Test]
    public function hamburger_button_visible_on_mobile_only(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();

        // REQ-9: Hamburger toggle button with aria-label exists
        $response->assertSee('aria-label="Open sidebar"', false);

        // REQ-9: Hamburger has bars-3 icon (hamburger menu SVG path)
        $response->assertSee('M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5', false);
    }
}
