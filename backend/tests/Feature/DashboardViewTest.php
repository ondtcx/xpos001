<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardViewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reflects_the_current_operational_state_instead_of_an_initial_bootstrap_message(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('XPOS - Núcleo operativo', false);
        $response->assertSee('Iteración 1 avanzada', false);
        $response->assertSee('El sistema ya sostiene operación local con trazabilidad transaccional y lectura auditiva.', false);
        $response->assertSee(route('sales.index'), false);
        $response->assertSee(route('purchases.index'), false);
        $response->assertDontSee('Base técnica inicial lista', false);
    }
}
