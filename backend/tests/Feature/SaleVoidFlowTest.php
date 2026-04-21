<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\SaleItemLotConsumption;
use App\Models\SalePresentation;
use App\Models\SalePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaleVoidFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_voids_a_sale_and_reverses_inventory_cash_receivable_and_payments(): void
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create(['name' => 'Cliente Uno', 'is_active' => true]);
        $this->actingAs($user);

        $setup = $this->createSaleScenario($user, $customer);
        $sale = $setup['sale'];
        $lot = $setup['lot'];
        $openCashSession = $setup['current_cash_session'];

        $response = $this->post(route('sales.void', $sale), [
            'void_reason' => 'Venta registrada por error',
        ]);

        $response->assertRedirect(route('sales.index'));
        $response->assertSessionHasNoErrors();

        $sale->refresh();
        $lot->refresh();
        $receivable = Receivable::query()->where('sale_id', $sale->id)->firstOrFail();
        $receivablePayment = ReceivablePayment::query()->where('receivable_id', $receivable->id)->firstOrFail();

        $this->assertTrue($sale->isVoided());
        $this->assertSame('Venta registrada por error', $sale->void_reason);
        $this->assertSame('10.000', $lot->available_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
            'movement_type' => 'sale_void_reversal',
        ]);

        $this->assertTrue($receivable->isCancelled());
        $this->assertSame(0, $receivable->pending_amount);
        $this->assertTrue($receivablePayment->isReversed());

        $this->assertDatabaseHas('cash_movements', [
            'cash_session_id' => $openCashSession->id,
            'movement_type' => 'sale_payment_reversal',
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
        ]);

        $this->assertDatabaseHas('cash_movements', [
            'cash_session_id' => $openCashSession->id,
            'movement_type' => 'receivable_payment_reversal',
            'reference_type' => 'receivable',
            'reference_id' => $receivable->id,
        ]);
    }

    #[Test]
    public function it_blocks_sale_void_when_there_is_no_open_cash_session_for_reversals(): void
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create(['name' => 'Cliente Dos', 'is_active' => true]);
        $this->actingAs($user);

        $setup = $this->createSaleScenario($user, $customer);
        $sale = $setup['sale'];

        CashSession::query()->where('status', 'open')->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $response = $this->from(route('sales.index'))->post(route('sales.void', $sale), [
            'void_reason' => 'Intento sin caja abierta',
        ]);

        $response->assertRedirect(route('sales.index'));
        $response->assertSessionHasErrors('sale');

        $sale->refresh();

        $this->assertTrue($sale->isConfirmed());
        $this->assertNull($sale->voided_at);
    }

    private function createSaleScenario(User $user, Customer $customer): array
    {
        $category = Category::query()->create(['name' => 'Bebidas', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Marca X', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $product = Product::query()->create([
            'name' => 'Producto Venta',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Variante Venta',
            'base_unit_id' => $baseUnit->id,
            'tracks_expiration' => false,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        $presentation = SalePresentation::query()->create([
            'product_variant_id' => $variant->id,
            'name' => 'Unidad',
            'conversion_factor' => 1,
            'is_default' => true,
            'is_active' => true,
        ]);

        SalePrice::query()->create([
            'sale_presentation_id' => $presentation->id,
            'price_amount' => 500,
            'min_price_amount' => 400,
            'starts_at' => now()->subDay(),
            'created_by' => $user->id,
            'reason' => 'Precio inicial',
        ]);

        $originalCashSession = CashSession::query()->create([
            'opened_by' => $user->id,
            'opened_at' => now()->subHours(3),
            'opening_amount' => 1000,
            'status' => 'closed',
            'closed_at' => now()->subHours(2),
        ]);

        $currentCashSession = CashSession::query()->create([
            'opened_by' => $user->id,
            'opened_at' => now()->subHour(),
            'opening_amount' => 500,
            'status' => 'open',
        ]);

        $sale = Sale::query()->create([
            'sold_at' => now()->subMinutes(30),
            'customer_id' => $customer->id,
            'cash_session_id' => $originalCashSession->id,
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'paid_amount' => 600,
            'credit_amount' => 400,
            'status' => Sale::STATUS_CONFIRMED,
            'created_by' => $user->id,
        ]);

        $saleItem = $sale->items()->create([
            'item_type' => 'product',
            'sale_presentation_id' => $presentation->id,
            'variant_id' => $variant->id,
            'description_snapshot' => 'Producto Venta',
            'quantity' => 2,
            'unit_price_amount' => 500,
            'original_unit_price_amount' => 500,
            'subtotal_amount' => 1000,
            'total_cost_amount' => 600,
            'total_profit_amount' => 400,
            'has_cost_warning' => false,
            'has_stock_warning' => false,
        ]);

        $lot = InventoryLot::query()->create([
            'variant_id' => $variant->id,
            'origin_type' => 'purchase',
            'origin_id' => 1,
            'received_at' => now()->subDays(2),
            'initial_quantity' => 10,
            'available_quantity' => 8,
            'bonus_quantity' => 0,
            'unit_cost_final_amount' => 300,
            'is_estimated' => false,
            'status' => 'active',
        ]);

        SaleItemLotConsumption::query()->create([
            'sale_item_id' => $saleItem->id,
            'lot_id' => $lot->id,
            'quantity' => 2,
            'unit_cost_amount' => 300,
            'total_cost_amount' => 600,
        ]);

        InventoryMovement::query()->create([
            'variant_id' => $variant->id,
            'lot_id' => $lot->id,
            'movement_type' => 'sale_output',
            'quantity' => -2,
            'unit_cost_amount' => 300,
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
            'movement_at' => $sale->sold_at,
            'notes' => 'Salida original',
            'created_by' => $user->id,
        ]);

        $sale->payments()->create([
            'payment_method' => 'cash',
            'amount' => 600,
            'received_at' => $sale->sold_at,
            'notes' => 'Pago original',
        ]);

        $receivable = Receivable::query()->create([
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'original_amount' => 400,
            'pending_amount' => 300,
            'opened_at' => $sale->sold_at,
            'status' => 'open',
        ]);

        ReceivablePayment::query()->create([
            'receivable_id' => $receivable->id,
            'cash_session_id' => $originalCashSession->id,
            'amount' => 100,
            'payment_method' => 'cash',
            'paid_at' => now()->subMinutes(10),
            'notes' => 'Abono original',
            'created_by' => $user->id,
        ]);

        CashMovement::query()->create([
            'cash_session_id' => $originalCashSession->id,
            'movement_type' => 'sale_payment',
            'amount' => 600,
            'payment_method' => 'cash',
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
            'notes' => 'Ingreso original venta',
            'created_by' => $user->id,
            'created_at' => $sale->sold_at,
        ]);

        CashMovement::query()->create([
            'cash_session_id' => $originalCashSession->id,
            'movement_type' => 'receivable_payment',
            'amount' => 100,
            'payment_method' => 'cash',
            'reference_type' => 'receivable',
            'reference_id' => $receivable->id,
            'notes' => 'Ingreso original abono',
            'created_by' => $user->id,
            'created_at' => now()->subMinutes(10),
        ]);

        return [
            'sale' => $sale,
            'lot' => $lot,
            'current_cash_session' => $currentCashSession,
        ];
    }
}
