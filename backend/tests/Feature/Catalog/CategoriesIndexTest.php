<?php

namespace Tests\Feature\Catalog;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoriesIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_categories_index_page_loads_successfully(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('categories.index'));

        $response->assertOk();
    }

    #[Test]
    public function it_displays_the_categories_title(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('categories.index'));

        $response->assertSee('Categorías', false);
    }

    #[Test]
    public function it_has_a_create_category_link(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('categories.index'));

        $response->assertSee(route('categories.create'), false);
    }

    #[Test]
    public function it_displays_seeded_categories(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::query()->create([
            'name' => 'Bebidas',
            'is_active' => true,
        ]);

        $response = $this->get(route('categories.index'));

        $response->assertSee('Bebidas', false);
    }

    #[Test]
    public function it_shows_an_empty_state_when_no_categories_exist(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('categories.index'));

        $response->assertOk();
        $response->assertSee('Aún no hay categorías', false);
    }

    #[Test]
    public function it_does_not_use_legacy_indigo_color(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('categories.index'));

        // Scoped assertion: check categories-specific patterns that previously used indigo.
        // Layout navigation (nav-link, responsive-nav-link — sidebar workstream,
        // out of scope) still renders border-indigo-400 etc., so we check only the
        // patterns that were in the categories index page content before the rewrite:
        //   - bg-indigo-600: create button
        //   - text-indigo-600: edit link color
        //   - hover:text-indigo-800: edit link hover
        $response->assertDontSee('bg-indigo-600', false);
        $response->assertDontSee('text-indigo-600', false);
        $response->assertDontSee('hover:text-indigo-800', false);
    }
}
