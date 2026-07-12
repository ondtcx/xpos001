<?php

namespace Tests\Feature\Catalog;

use App\Models\BaseUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BaseUnitsIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_base_units_index_page_loads_successfully(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('base-units.index'));

        $response->assertOk();
    }

    #[Test]
    public function it_displays_the_base_units_title(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('base-units.index'));

        $response->assertSee('Unidades base', false);
    }

    #[Test]
    public function it_has_a_create_base_unit_link(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('base-units.index'));

        $response->assertSee(route('base-units.create'), false);
    }

    #[Test]
    public function it_displays_seeded_base_units(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $baseUnit = BaseUnit::query()->create([
            'name' => 'Kilogramo',
            'symbol' => 'kg',
        ]);

        $response = $this->get(route('base-units.index'));

        $response->assertSee('Kilogramo', false);
        $response->assertSee('kg', false);
    }

    #[Test]
    public function it_shows_an_empty_state_when_no_base_units_exist(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('base-units.index'));

        $response->assertOk();
        $response->assertSee('Aún no hay unidades base registradas.', false);
    }

    #[Test]
    public function it_does_not_use_legacy_indigo_color(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('base-units.index'));

        // Scoped assertion: check base-units-specific patterns that previously used indigo.
        // Layout navigation (nav-link, responsive-nav-link — sidebar workstream,
        // out of scope) still renders border-indigo-400 etc., so we check only the
        // patterns that were in the base-units index page content before the rewrite:
        //   - bg-indigo-600: create button
        //   - text-indigo-600: edit link color
        //   - hover:text-indigo-800: edit link hover
        $response->assertDontSee('bg-indigo-600', false);
        $response->assertDontSee('text-indigo-600', false);
        $response->assertDontSee('hover:text-indigo-800', false);
    }
}
