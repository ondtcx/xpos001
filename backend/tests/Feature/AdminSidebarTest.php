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
}
