<?php

namespace Tests\Feature\Catalog;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BrandsIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_brands_index_page_loads_successfully(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('brands.index'));

        $response->assertOk();
    }

    #[Test]
    public function it_displays_the_brands_title(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('brands.index'));

        $response->assertSee('Marcas', false);
    }

    #[Test]
    public function it_has_a_create_brand_link(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('brands.index'));

        $response->assertSee(route('brands.create'), false);
    }

    #[Test]
    public function it_displays_seeded_brands(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $brand = Brand::query()->create([
            'name' => 'Marca Ejemplo',
            'is_active' => true,
        ]);

        $response = $this->get(route('brands.index'));

        $response->assertSee('Marca Ejemplo', false);
    }

    #[Test]
    public function it_shows_an_empty_state_when_no_brands_exist(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('brands.index'));

        $response->assertOk();
        $response->assertSee('Aún no hay marcas registradas.', false);
    }

    #[Test]
    public function it_does_not_use_legacy_indigo_color(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('brands.index'));

        // Scoped assertion: check brands-specific patterns that previously used indigo.
        // Layout navigation (nav-link, responsive-nav-link — sidebar workstream,
        // out of scope) still renders border-indigo-400 etc., so we check only the
        // patterns that were in the brands index page content before the rewrite:
        //   - bg-indigo-600: create button
        //   - text-indigo-600: edit link color
        //   - hover:text-indigo-800: edit link hover
        $response->assertDontSee('bg-indigo-600', false);
        $response->assertDontSee('text-indigo-600', false);
        $response->assertDontSee('hover:text-indigo-800', false);
    }
}
