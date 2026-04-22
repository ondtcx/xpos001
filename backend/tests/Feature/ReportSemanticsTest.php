<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportSemanticsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_net_operational_totals_with_gross_and_reversal_breakdowns(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::query()->create([
            'name' => 'Cliente Reportes',
            'is_active' => true,
        ]);

        $category = Category::query()->create(['name' => 'Categoría Reportes', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Marca Reportes', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $product = Product::query()->create([
            'name' => 'Producto Reportes',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Variante Reportes',
            'base_unit_id' => $baseUnit->id,
            'tracks_expiration' => false,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        InventoryLot::query()->create([
            'variant_id' => $variant->id,
            'origin_type' => 'purchase',
            'origin_id' => 1,
            'received_at' => now()->subDay(),
            'initial_quantity' => 4,
            'available_quantity' => 4,
            'bonus_quantity' => 0,
            'unit_cost_final_amount' => 300,
            'is_estimated' => false,
            'status' => 'active',
        ]);

        $cashSession = CashSession::query()->create([
            'opened_by' => $user->id,
            'opened_at' => now()->subHours(4),
            'opening_amount' => 500,
            'status' => 'open',
        ]);

        $confirmedSale = Sale::query()->create([
            'sold_at' => now()->subHours(3),
            'customer_id' => $customer->id,
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 700,
            'credit_amount' => 300,
            'status' => Sale::STATUS_CONFIRMED,
            'created_by' => $user->id,
        ]);

        SaleItem::query()->create([
            'sale_id' => $confirmedSale->id,
            'item_type' => 'product',
            'variant_id' => $variant->id,
            'description_snapshot' => 'Venta confiable',
            'quantity' => 1,
            'unit_price_amount' => 1000,
            'subtotal_amount' => 1000,
            'total_cost_amount' => 600,
            'total_profit_amount' => 400,
            'has_cost_warning' => false,
            'has_stock_warning' => false,
        ]);

        $warningSale = Sale::query()->create([
            'sold_at' => now()->subHours(2),
            'customer_id' => $customer->id,
            'subtotal_amount' => 500,
            'discount_amount' => 0,
            'total_amount' => 500,
            'paid_amount' => 500,
            'credit_amount' => 0,
            'status' => Sale::STATUS_CONFIRMED,
            'created_by' => $user->id,
        ]);

        SaleItem::query()->create([
            'sale_id' => $warningSale->id,
            'item_type' => 'product',
            'variant_id' => $variant->id,
            'description_snapshot' => 'Venta con warning',
            'quantity' => 1,
            'unit_price_amount' => 500,
            'subtotal_amount' => 500,
            'total_cost_amount' => 300,
            'total_profit_amount' => 200,
            'has_cost_warning' => true,
            'has_stock_warning' => false,
        ]);

        $voidedSale = Sale::query()->create([
            'sold_at' => now()->subHour(),
            'customer_id' => $customer->id,
            'subtotal_amount' => 800,
            'discount_amount' => 0,
            'total_amount' => 800,
            'paid_amount' => 800,
            'credit_amount' => 0,
            'status' => Sale::STATUS_VOIDED,
            'voided_at' => now()->subMinutes(30),
            'voided_by' => $user->id,
            'void_reason' => 'Prueba de anulación',
            'created_by' => $user->id,
        ]);

        SaleItem::query()->create([
            'sale_id' => $voidedSale->id,
            'item_type' => 'product',
            'variant_id' => $variant->id,
            'description_snapshot' => 'Venta anulada',
            'quantity' => 1,
            'unit_price_amount' => 800,
            'subtotal_amount' => 800,
            'total_cost_amount' => 500,
            'total_profit_amount' => 300,
            'has_cost_warning' => false,
            'has_stock_warning' => false,
        ]);

        Purchase::query()->create([
            'purchased_at' => now()->subHours(4),
            'payment_type' => 'cash',
            'entry_mode' => Purchase::ENTRY_MODE_QUICK,
            'subtotal_amount' => 2000,
            'total_amount' => 2000,
            'status' => Purchase::STATUS_CONFIRMED,
            'created_by' => $user->id,
        ]);

        Purchase::query()->create([
            'purchased_at' => now()->subHours(3),
            'payment_type' => 'cash',
            'entry_mode' => Purchase::ENTRY_MODE_DETAILED,
            'subtotal_amount' => 600,
            'total_amount' => 600,
            'status' => Purchase::STATUS_VOIDED,
            'voided_at' => now()->subHours(2),
            'voided_by' => $user->id,
            'void_reason' => 'Compra anulada',
            'created_by' => $user->id,
        ]);

        $receivable = Receivable::query()->create([
            'customer_id' => $customer->id,
            'sale_id' => $confirmedSale->id,
            'original_amount' => 300,
            'pending_amount' => 200,
            'opened_at' => now()->subHours(3),
            'status' => 'open',
        ]);

        ReceivablePayment::query()->create([
            'receivable_id' => $receivable->id,
            'amount' => 100,
            'payment_method' => 'cash',
            'paid_at' => now()->subHours(2),
            'created_by' => $user->id,
            'is_reversed' => false,
        ]);

        ReceivablePayment::query()->create([
            'receivable_id' => $receivable->id,
            'amount' => 50,
            'payment_method' => 'cash',
            'paid_at' => now()->subHour(),
            'created_by' => $user->id,
            'is_reversed' => true,
            'reversed_at' => now()->subMinutes(20),
            'reversed_by' => $user->id,
            'reversal_reason' => 'Abono revertido',
        ]);

        CashMovement::query()->create([
            'cash_session_id' => $cashSession->id,
            'movement_type' => 'sale_payment',
            'amount' => 700,
            'payment_method' => 'cash',
            'reference_type' => 'sale',
            'reference_id' => $confirmedSale->id,
            'created_by' => $user->id,
            'created_at' => now()->subHours(3),
        ]);

        CashMovement::query()->create([
            'cash_session_id' => $cashSession->id,
            'movement_type' => 'receivable_payment',
            'amount' => 100,
            'payment_method' => 'cash',
            'reference_type' => 'receivable',
            'reference_id' => $receivable->id,
            'created_by' => $user->id,
            'created_at' => now()->subHours(2),
        ]);

        CashMovement::query()->create([
            'cash_session_id' => $cashSession->id,
            'movement_type' => 'sale_payment_reversal',
            'amount' => -800,
            'payment_method' => 'cash',
            'reference_type' => 'sale',
            'reference_id' => $voidedSale->id,
            'created_by' => $user->id,
            'created_at' => now()->subMinutes(30),
        ]);

        CashMovement::query()->create([
            'cash_session_id' => $cashSession->id,
            'movement_type' => 'receivable_payment_reversal',
            'amount' => -50,
            'payment_method' => 'cash',
            'reference_type' => 'receivable',
            'reference_id' => $receivable->id,
            'created_by' => $user->id,
            'created_at' => now()->subMinutes(20),
        ]);

        CashMovement::query()->create([
            'cash_session_id' => $cashSession->id,
            'movement_type' => 'opening',
            'amount' => 500,
            'payment_method' => 'cash',
            'reference_type' => 'cash_session',
            'reference_id' => $cashSession->id,
            'created_by' => $user->id,
            'created_at' => now()->subHours(4),
        ]);

        $response = $this->get(route('reports.index', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Ventas netas', false);
        $response->assertSee('$15.00', false);
        $response->assertSee('Bruto $23.00 · anuladas $8.00', false);
        $response->assertSee('Utilidad confiable', false);
        $response->assertSee('$4.00', false);
        $response->assertSee('Excluida por warnings $2.00 · anulada $3.00', false);
        $response->assertSee('Compras netas', false);
        $response->assertSee('Bruto $26.00 · anuladas $6.00', false);
        $response->assertSee('Abonos netos', false);
        $response->assertSee('Brutos $1.50 · revertidos $0.50', false);
        $response->assertSee('Operativa', false);
        $response->assertSee('$8.00', false);
        $response->assertSee('Reversas', false);
        $response->assertSee('$-8.50', false);
        $response->assertSee('Neto final', false);
        $response->assertSee('$4.50', false);
    }
}
