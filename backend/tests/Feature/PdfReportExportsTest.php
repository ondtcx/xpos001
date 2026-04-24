<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PdfReportExportsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_exports_sales_pdf(): void
    {
        [$user, $sale] = $this->seedSaleScenario();

        $response = $this->actingAs($user)->get(route('reports.export.sales-pdf', [
            'start_date' => $sale->sold_at->toDateString(),
            'end_date' => $sale->sold_at->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    #[Test]
    public function it_exports_purchases_pdf(): void
    {
        [$user, $purchase] = $this->seedPurchaseScenario();

        $response = $this->actingAs($user)->get(route('reports.export.purchases-pdf', [
            'start_date' => $purchase->purchased_at->toDateString(),
            'end_date' => $purchase->purchased_at->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    #[Test]
    public function it_exports_receivables_pdf(): void
    {
        [$user, $receivable] = $this->seedReceivableScenario();

        $response = $this->actingAs($user)->get(route('reports.export.receivables-pdf', [
            'start_date' => $receivable->opened_at->toDateString(),
            'end_date' => $receivable->opened_at->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    private function seedSaleScenario(): array
    {
        $user = User::factory()->create(['name' => 'Usuario Exporta']);
        $customer = Customer::query()->create(['name' => 'Cliente Exportación', 'is_active' => true]);
        $category = Category::query()->create(['name' => 'Categoría Exporta', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Marca Exporta', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);
        $product = Product::query()->create(['name' => 'Producto Exportación', 'category_id' => $category->id, 'brand_id' => $brand->id, 'status' => 'active']);
        $variant = ProductVariant::query()->create(['product_id' => $product->id, 'name' => 'Variante Exportación', 'base_unit_id' => $baseUnit->id, 'tracks_expiration' => false, 'is_returnable' => false, 'is_active' => true]);
        $sale = Sale::query()->create(['sold_at' => now()->subMinutes(15), 'customer_id' => $customer->id, 'subtotal_amount' => 900, 'discount_amount' => 0, 'total_amount' => 900, 'paid_amount' => 500, 'credit_amount' => 400, 'status' => Sale::STATUS_CONFIRMED, 'notes' => 'Nota exportable', 'created_by' => $user->id]);
        $sale->items()->create(['item_type' => 'product', 'variant_id' => $variant->id, 'description_snapshot' => 'Producto Exportación — Variante Exportación', 'quantity' => 2, 'unit_price_amount' => 450, 'manual_unit_price_amount' => 450, 'has_manual_price_override' => true, 'manual_price_reason' => 'Promoción exportable', 'subtotal_amount' => 900, 'total_cost_amount' => 500, 'total_profit_amount' => 400, 'has_stock_warning' => true, 'has_cost_warning' => false]);

        return [$user, $sale];
    }

    private function seedPurchaseScenario(): array
    {
        $user = User::factory()->create(['name' => 'Usuario Compra Exporta']);
        $supplier = Supplier::query()->create(['name' => 'Proveedor Exportación', 'is_active' => true]);
        $category = Category::query()->create(['name' => 'Categoría Compra Exporta', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Marca Compra Exporta', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);
        $product = Product::query()->create(['name' => 'Producto Compra Exportación', 'category_id' => $category->id, 'brand_id' => $brand->id, 'status' => 'active']);
        $variant = ProductVariant::query()->create(['product_id' => $product->id, 'name' => 'Variante Compra Exportación', 'base_unit_id' => $baseUnit->id, 'tracks_expiration' => true, 'is_returnable' => false, 'is_active' => true]);
        $purchase = Purchase::query()->create(['supplier_id' => $supplier->id, 'invoice_number' => 'FAC-EXPORT-01', 'purchased_at' => now()->subMinutes(20), 'payment_type' => 'transfer', 'entry_mode' => Purchase::ENTRY_MODE_DETAILED, 'subtotal_amount' => 2000, 'global_discount_amount' => 100, 'global_tax_iva_amount' => 200, 'global_tax_other_amount' => 50, 'extra_costs_amount' => 75, 'total_amount' => 2225, 'notes' => 'Compra exportable', 'status' => Purchase::STATUS_CONFIRMED, 'created_by' => $user->id]);
        PurchaseItem::query()->create(['purchase_id' => $purchase->id, 'variant_id' => $variant->id, 'line_type' => PurchaseItem::LINE_TYPE_NORMAL, 'quantity' => 10, 'bonus_quantity' => 2, 'unit_cost_base_amount' => 200, 'line_subtotal_amount' => 2000, 'line_discount_amount' => 50, 'tax_iva_amount' => 120, 'tax_other_amount' => 20, 'allocated_global_discount_amount' => 50, 'allocated_global_tax_iva_amount' => 80, 'allocated_global_tax_other_amount' => 30, 'allocated_extra_costs_amount' => 75, 'unit_cost_final_amount' => 188, 'total_cost_amount' => 2255, 'expiration_date' => now()->addMonths(3)->toDateString(), 'notes' => 'Línea exportable']);

        return [$user, $purchase];
    }

    private function seedReceivableScenario(): array
    {
        $user = User::factory()->create(['name' => 'Usuario Fiado Exporta']);
        $customer = Customer::query()->create(['name' => 'Cliente Fiado Exporta', 'is_active' => true]);
        $sale = Sale::query()->create(['sold_at' => now()->subMinutes(30), 'customer_id' => $customer->id, 'subtotal_amount' => 1200, 'discount_amount' => 0, 'total_amount' => 1200, 'paid_amount' => 0, 'credit_amount' => 1200, 'status' => Sale::STATUS_CONFIRMED, 'created_by' => $user->id]);
        $receivable = Receivable::query()->create(['customer_id' => $customer->id, 'sale_id' => $sale->id, 'original_amount' => 1200, 'pending_amount' => 500, 'opened_at' => now()->subMinutes(30), 'status' => 'open']);
        ReceivablePayment::query()->create(['receivable_id' => $receivable->id, 'amount' => 400, 'payment_method' => 'cash', 'paid_at' => now()->subMinutes(20), 'notes' => 'Abono vigente', 'created_by' => $user->id, 'is_reversed' => false]);
        ReceivablePayment::query()->create(['receivable_id' => $receivable->id, 'amount' => 300, 'payment_method' => 'transfer', 'paid_at' => now()->subMinutes(10), 'notes' => 'Abono revertido', 'created_by' => $user->id, 'is_reversed' => true, 'reversed_at' => now()->subMinutes(5), 'reversal_reason' => 'Reversa de prueba']);

        return [$user, $receivable];
    }
}
