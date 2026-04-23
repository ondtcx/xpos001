<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReceivableShowTrackingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_collection_tracking_metrics_for_a_receivable(): void
    {
        Carbon::setTestNow('2026-04-22 10:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::query()->create(['name' => 'Cliente Seguimiento', 'is_active' => true]);
        $sale = Sale::query()->create([
            'sold_at' => '2026-03-10 09:00:00',
            'customer_id' => $customer->id,
            'subtotal_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'paid_amount' => 0,
            'credit_amount' => 0,
            'status' => Sale::STATUS_CONFIRMED,
            'created_by' => $user->id,
        ]);

        $receivable = Receivable::query()->create([
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'original_amount' => 5000,
            'pending_amount' => 2000,
            'opened_at' => '2026-03-10 09:00:00',
            'status' => 'open',
        ]);

        ReceivablePayment::query()->create([
            'receivable_id' => $receivable->id,
            'cash_session_id' => null,
            'amount' => 1000,
            'payment_method' => 'cash',
            'paid_at' => '2026-03-20 12:00:00',
            'notes' => null,
            'created_by' => $user->id,
        ]);

        ReceivablePayment::query()->create([
            'receivable_id' => $receivable->id,
            'cash_session_id' => null,
            'amount' => 2000,
            'payment_method' => 'transfer',
            'paid_at' => '2026-04-15 17:30:00',
            'notes' => null,
            'created_by' => $user->id,
        ]);

        $response = $this->get(route('receivables.show', $receivable));

        $response->assertOk();
        $response->assertSee('Seguimiento de cobranza', false);
        $response->assertSee('43 días', false);
        $response->assertSee('$30.00', false);
        $response->assertSee('60%', false);
        $response->assertSee('2026-04-15 17:30', false);
        $response->assertSee('Deuda antigua', false);

        $receivableTracking = $response->viewData('receivableTracking');

        $this->assertSame(43, $receivableTracking['days_open']);
        $this->assertSame(3000, $receivableTracking['paid_amount']);
        $this->assertSame(60, $receivableTracking['collection_progress']);
        $this->assertSame('Deuda antigua', $receivableTracking['aging_label']);

        Carbon::setTestNow();
    }
}
