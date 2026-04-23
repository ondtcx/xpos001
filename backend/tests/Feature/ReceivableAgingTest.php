<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReceivableAgingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_groups_open_receivables_by_age_buckets(): void
    {
        Carbon::setTestNow('2026-04-22 10:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        $customerA = Customer::query()->create(['name' => 'Cliente A', 'is_active' => true]);
        $customerB = Customer::query()->create(['name' => 'Cliente B', 'is_active' => true]);
        $customerC = Customer::query()->create(['name' => 'Cliente C', 'is_active' => true]);
        $customerD = Customer::query()->create(['name' => 'Cliente D', 'is_active' => true]);

        $saleA = $this->createSale($user, $customerA, '2026-04-20 09:00:00');
        $saleB = $this->createSale($user, $customerB, '2026-04-10 09:00:00');
        $saleC = $this->createSale($user, $customerC, '2026-03-01 09:00:00');
        $saleD = $this->createSale($user, $customerD, '2026-04-01 09:00:00');

        Receivable::query()->create([
            'customer_id' => $customerA->id,
            'sale_id' => $saleA->id,
            'original_amount' => 1000,
            'pending_amount' => 1000,
            'opened_at' => '2026-04-20 09:00:00',
            'status' => 'open',
        ]);

        Receivable::query()->create([
            'customer_id' => $customerB->id,
            'sale_id' => $saleB->id,
            'original_amount' => 2000,
            'pending_amount' => 1500,
            'opened_at' => '2026-04-10 09:00:00',
            'status' => 'open',
        ]);

        Receivable::query()->create([
            'customer_id' => $customerC->id,
            'sale_id' => $saleC->id,
            'original_amount' => 3000,
            'pending_amount' => 2500,
            'opened_at' => '2026-03-01 09:00:00',
            'status' => 'open',
        ]);

        Receivable::query()->create([
            'customer_id' => $customerD->id,
            'sale_id' => $saleD->id,
            'original_amount' => 4000,
            'pending_amount' => 0,
            'opened_at' => '2026-04-01 09:00:00',
            'status' => 'paid',
        ]);

        $response = $this->get(route('receivables.index'));

        $response->assertOk();
        $response->assertSee('Antigüedad de deuda', false);
        $response->assertSee('0–7 días', false);
        $response->assertSee('8–30 días', false);
        $response->assertSee('31+ días', false);
        $response->assertSee('Saldo abierto:', false);
        $response->assertSee('$50.00', false);
        $response->assertSee('2 días', false);
        $response->assertSee('12 días', false);
        $response->assertSee('52 días', false);

        $receivableAging = $response->viewData('receivableAging');

        $this->assertSame(3, $receivableAging['open_count']);
        $this->assertSame(5000, $receivableAging['open_pending_amount']);
        $this->assertSame(1, $receivableAging['buckets'][0]['count']);
        $this->assertSame(1000, $receivableAging['buckets'][0]['pending_amount']);
        $this->assertSame(1, $receivableAging['buckets'][1]['count']);
        $this->assertSame(1500, $receivableAging['buckets'][1]['pending_amount']);
        $this->assertSame(1, $receivableAging['buckets'][2]['count']);
        $this->assertSame(2500, $receivableAging['buckets'][2]['pending_amount']);

        Carbon::setTestNow();
    }

    private function createSale(User $user, Customer $customer, string $soldAt): Sale
    {
        return Sale::query()->create([
            'sold_at' => $soldAt,
            'customer_id' => $customer->id,
            'subtotal_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'paid_amount' => 0,
            'credit_amount' => 0,
            'status' => Sale::STATUS_CONFIRMED,
            'created_by' => $user->id,
        ]);
    }
}
