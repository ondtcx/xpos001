<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReceivableReportExportsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_exports_receivables_csv_with_portfolio_status_columns(): void
    {
        [$user, $receivable] = $this->seedReceivableExportScenario();

        $response = $this->actingAs($user)->get(route('reports.export.receivables-csv', [
            'start_date' => $receivable->opened_at->toDateString(),
            'end_date' => $receivable->opened_at->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('cuenta_id,fecha_apertura,cliente,venta_id,usuario_venta,monto_original,saldo_pendiente,estado,cancelada_por_anulacion,cancelada_en,motivo_cancelacion', $content);
        $this->assertStringContainsString((string) $receivable->id, $content);
        $this->assertStringContainsString('Cliente Fiado Exporta', $content);
        $this->assertStringContainsString('12.00', $content);
        $this->assertStringContainsString('5.00', $content);
        $this->assertStringContainsString('open', $content);
    }

    #[Test]
    public function it_exports_receivable_payments_csv_including_reversed_payments(): void
    {
        [$user, $receivable, $payment, $reversedPayment] = $this->seedReceivableExportScenario();
        $startDate = min($payment->paid_at->toDateString(), $reversedPayment->paid_at->toDateString());
        $endDate = max($payment->paid_at->toDateString(), $reversedPayment->paid_at->toDateString());

        $response = $this->actingAs($user)->get(route('reports.export.receivable-payments-csv', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('abono_id,cuenta_id,venta_id,fecha_abono,cliente,usuario_registro,metodo_pago,monto,esta_revertido,fecha_reversa,motivo_reversa,notas', $content);
        $this->assertStringContainsString((string) $payment->id, $content);
        $this->assertStringContainsString((string) $reversedPayment->id, $content);
        $this->assertStringContainsString('Cliente Fiado Exporta', $content);
        $this->assertStringContainsString('cash,4.00,no', $content);
        $this->assertStringContainsString('transfer,3.00,si', $content);
        $this->assertStringContainsString('Reversa de prueba', $content);
    }

    private function seedReceivableExportScenario(): array
    {
        $user = User::factory()->create(['name' => 'Usuario Fiado Exporta']);
        $customer = Customer::query()->create(['name' => 'Cliente Fiado Exporta', 'is_active' => true]);

        $sale = Sale::query()->create([
            'sold_at' => now()->subMinutes(30),
            'customer_id' => $customer->id,
            'subtotal_amount' => 1200,
            'discount_amount' => 0,
            'total_amount' => 1200,
            'paid_amount' => 0,
            'credit_amount' => 1200,
            'status' => Sale::STATUS_CONFIRMED,
            'created_by' => $user->id,
        ]);

        $receivable = Receivable::query()->create([
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'original_amount' => 1200,
            'pending_amount' => 500,
            'opened_at' => now()->subMinutes(30),
            'status' => 'open',
        ]);

        $payment = ReceivablePayment::query()->create([
            'receivable_id' => $receivable->id,
            'amount' => 400,
            'payment_method' => 'cash',
            'paid_at' => now()->subMinutes(20),
            'notes' => 'Abono vigente',
            'created_by' => $user->id,
            'is_reversed' => false,
        ]);

        $reversedPayment = ReceivablePayment::query()->create([
            'receivable_id' => $receivable->id,
            'amount' => 300,
            'payment_method' => 'transfer',
            'paid_at' => now()->subMinutes(10),
            'notes' => 'Abono revertido',
            'created_by' => $user->id,
            'is_reversed' => true,
            'reversed_at' => now()->subMinutes(5),
            'reversal_reason' => 'Reversa de prueba',
        ]);

        return [$user, $receivable, $payment, $reversedPayment];
    }
}
