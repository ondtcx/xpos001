<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\MinimarketDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MinimarketDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_minimarket_demo_seeder_builds_a_reusable_operational_dataset(): void
    {
        $this->seed(MinimarketDemoSeeder::class);

        $admin = User::query()->where('username', 'admin')->first();
        $assistant = User::query()->where('username', 'cajero')->first();

        $this->assertNotNull($admin);
        $this->assertNotNull($assistant);
        $this->assertTrue(Hash::check('cajero12345', $assistant->password));

        $this->assertDatabaseCount('products', 18);
        $this->assertDatabaseCount('suppliers', 3);
        $this->assertDatabaseCount('customers', 3);
        $this->assertDatabaseCount('cash_sessions', 2);

        $this->assertDatabaseHas('cash_sessions', ['status' => 'open']);
        $this->assertDatabaseHas('cash_sessions', ['status' => 'closed']);
        $this->assertDatabaseHas('sales', ['status' => Sale::STATUS_CONFIRMED]);
        $this->assertDatabaseHas('purchases', ['status' => Purchase::STATUS_VOIDED]);
        $this->assertDatabaseHas('sale_items', ['has_stock_warning' => true]);
        $this->assertDatabaseHas('receivables', ['status' => 'open']);
        $this->assertDatabaseHas('cash_movements', ['movement_type' => 'receivable_payment']);

        $currentSession = CashSession::query()->where('status', 'open')->first();
        $openReceivable = Receivable::query()->where('status', 'open')->first();

        $this->assertNotNull($currentSession);
        $this->assertNotNull($openReceivable);
        $this->assertGreaterThan(0, $openReceivable->pending_amount);
        $this->assertSame($currentSession->id, Sale::query()->latest('sold_at')->first()->cash_session_id);
    }
}
