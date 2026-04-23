<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CashConsolidationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_consolidated_closed_cash_sessions_by_closed_date_range(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        CashSession::query()->create([
            'opened_by' => $user->id,
            'opened_at' => '2026-04-01 08:00:00',
            'opening_amount' => 1000,
            'status' => 'closed',
            'closed_at' => '2026-04-01 18:00:00',
            'expected_cash_amount' => 1500,
            'counted_cash_amount' => 1400,
            'expected_transfer_amount' => 500,
            'difference_amount' => -100,
        ]);

        CashSession::query()->create([
            'opened_by' => $user->id,
            'opened_at' => '2026-04-05 08:00:00',
            'opening_amount' => 2000,
            'status' => 'closed',
            'closed_at' => '2026-04-05 18:00:00',
            'expected_cash_amount' => 3000,
            'counted_cash_amount' => 3200,
            'expected_transfer_amount' => 700,
            'difference_amount' => 200,
        ]);

        CashMovement::query()->create([
            'cash_session_id' => 1,
            'movement_type' => 'opening',
            'amount' => 1000,
            'payment_method' => 'cash',
            'reference_type' => 'cash_session',
            'reference_id' => 1,
            'created_by' => $user->id,
            'created_at' => '2026-04-01 08:00:00',
        ]);

        CashMovement::query()->create([
            'cash_session_id' => 1,
            'movement_type' => 'sale_payment',
            'amount' => 500,
            'payment_method' => 'cash',
            'reference_type' => 'sale',
            'reference_id' => 11,
            'created_by' => $user->id,
            'created_at' => '2026-04-01 10:00:00',
        ]);

        CashMovement::query()->create([
            'cash_session_id' => 1,
            'movement_type' => 'expense',
            'amount' => 200,
            'payment_method' => 'cash',
            'reference_type' => 'manual_cash_movement',
            'reference_id' => null,
            'created_by' => $user->id,
            'created_at' => '2026-04-01 12:00:00',
        ]);

        CashMovement::query()->create([
            'cash_session_id' => 2,
            'movement_type' => 'sale_payment',
            'amount' => 700,
            'payment_method' => 'transfer',
            'reference_type' => 'sale',
            'reference_id' => 12,
            'created_by' => $user->id,
            'created_at' => '2026-04-05 10:00:00',
        ]);

        CashMovement::query()->create([
            'cash_session_id' => 2,
            'movement_type' => 'receivable_payment',
            'amount' => 300,
            'payment_method' => 'cash',
            'reference_type' => 'receivable',
            'reference_id' => 21,
            'created_by' => $user->id,
            'created_at' => '2026-04-05 11:00:00',
        ]);

        CashSession::query()->create([
            'opened_by' => $user->id,
            'opened_at' => '2026-03-28 08:00:00',
            'opening_amount' => 500,
            'status' => 'closed',
            'closed_at' => '2026-03-28 18:00:00',
            'expected_cash_amount' => 9999,
            'counted_cash_amount' => 9999,
            'expected_transfer_amount' => 9999,
            'difference_amount' => 9999,
        ]);

        CashSession::query()->create([
            'opened_by' => $user->id,
            'opened_at' => '2026-04-06 08:00:00',
            'opening_amount' => 500,
            'status' => 'open',
        ]);

        $response = $this->get(route('cash.index', [
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
        ]));

        $response->assertOk();
        $response->assertSee('Caja consolidada', false);
        $response->assertSee('Resumen histórico por fecha de cierre.', false);
        $response->assertSee('>2<', false);
        $response->assertSee('$45.00', false);
        $response->assertSee('$46.00', false);
        $response->assertSee('$12.00', false);
        $response->assertSee('$1.00', false);
        $response->assertSee('Cierres exactos', false);
        $response->assertSee('Cierres con faltante', false);
        $response->assertSee('Cierres con sobrante', false);
        $response->assertSee('Faltante acumulado', false);
        $response->assertSee('Sobrante acumulado', false);
        $response->assertSee('Peor desvío del período', false);
        $response->assertSee('Composición del movimiento de caja', false);
        $response->assertSee('Apertura', false);
        $response->assertSee('Pago de venta', false);
        $response->assertSee('Gasto', false);
        $response->assertSee('Abono de cuenta por cobrar', false);
        $response->assertSee('Caja #1', false);
        $response->assertSee('Caja #2', false);

        $closedSessionIds = $response->viewData('closedSessions')->pluck('id')->all();
        $cashSummary = $response->viewData('cashSummary');
        $cashMovementAnalysis = $response->viewData('cashMovementAnalysis');

        $this->assertSame([2, 1], $closedSessionIds);
        $this->assertSame(0, $cashSummary['balanced_sessions_count']);
        $this->assertSame(1, $cashSummary['shortage_sessions_count']);
        $this->assertSame(1, $cashSummary['surplus_sessions_count']);
        $this->assertSame(100, $cashSummary['shortage_total_amount']);
        $this->assertSame(200, $cashSummary['surplus_total_amount']);
        $this->assertSame(100, $cashSummary['largest_shortage_amount']);
        $this->assertSame(200, $cashSummary['largest_surplus_amount']);
        $this->assertSame(1600, $cashMovementAnalysis['totals']['cash_amount']);
        $this->assertSame(700, $cashMovementAnalysis['totals']['transfer_amount']);
        $this->assertSame(0, $cashMovementAnalysis['totals']['other_amount']);
        $this->assertSame(2300, $cashMovementAnalysis['totals']['total_amount']);
    }

    #[Test]
    public function it_rejects_invalid_cash_consolidation_ranges(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->from(route('cash.index'))->get(route('cash.index', [
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-01',
        ]));

        $response->assertRedirect(route('cash.index'));
        $response->assertSessionHasErrors('end_date');
    }
}
