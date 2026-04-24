<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportExportsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_exports_sales_summary_csv_with_operational_flags(): void
    {
        [$user, $sale] = $this->seedSaleExportScenario();

        $response = $this->actingAs($user)->get(route('reports.export.sales-summary-csv', [
            'start_date' => $sale->sold_at->toDateString(),
            'end_date' => $sale->sold_at->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();

        $this->assertStringContainsString('venta_id,fecha,cliente,usuario,estado,total,pagado,fiado,items,tiene_override_precio,tiene_warning_stock,tiene_warning_costo,motivo_anulacion,anulada_por,anulada_en,notas', $content);
        $this->assertStringContainsString((string) $sale->id, $content);
        $this->assertStringContainsString('Cliente Exportación', $content);
        $this->assertStringContainsString('con_saldo_pendiente', $content);
        $this->assertStringContainsString('si,si,no', $content);
        $this->assertStringContainsString('Nota exportable', $content);
    }

    #[Test]
    public function it_exports_sales_lines_csv_with_line_level_audit_columns(): void
    {
        [$user, $sale] = $this->seedSaleExportScenario();

        $response = $this->actingAs($user)->get(route('reports.export.sales-lines-csv', [
            'start_date' => $sale->sold_at->toDateString(),
            'end_date' => $sale->sold_at->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();

        $this->assertStringContainsString('venta_id,linea_id,fecha,cliente,usuario,estado_venta,producto,variante,descripcion,cantidad,precio_original,precio_aplicado,override_precio,motivo_override,warning_stock,warning_costo,costo_total,utilidad_total,motivo_anulacion_venta', $content);
        $this->assertStringContainsString((string) $sale->id, $content);
        $this->assertStringContainsString('Producto Exportación', $content);
        $this->assertStringContainsString('Promoción exportable', $content);
        $this->assertStringContainsString('si,no', $content);
        $this->assertStringContainsString('4.50', $content);
    }

    private function seedSaleExportScenario(): array
    {
        $user = User::factory()->create(['name' => 'Usuario Exporta']);
        $customer = Customer::query()->create(['name' => 'Cliente Exportación', 'is_active' => true]);
        $category = Category::query()->create(['name' => 'Categoría Exporta', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Marca Exporta', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $product = Product::query()->create([
            'name' => 'Producto Exportación',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Variante Exportación',
            'base_unit_id' => $baseUnit->id,
            'tracks_expiration' => false,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        $sale = Sale::query()->create([
            'sold_at' => now()->subMinutes(15),
            'customer_id' => $customer->id,
            'subtotal_amount' => 900,
            'discount_amount' => 0,
            'total_amount' => 900,
            'paid_amount' => 500,
            'credit_amount' => 400,
            'status' => Sale::STATUS_CONFIRMED,
            'notes' => 'Nota exportable',
            'created_by' => $user->id,
        ]);

        $sale->items()->create([
            'item_type' => 'product',
            'variant_id' => $variant->id,
            'description_snapshot' => 'Producto Exportación — Variante Exportación',
            'quantity' => 2,
            'unit_price_amount' => 450,
            'original_unit_price_amount' => 500,
            'manual_unit_price_amount' => 450,
            'has_manual_price_override' => true,
            'manual_price_reason' => 'Promoción exportable',
            'subtotal_amount' => 900,
            'total_cost_amount' => 500,
            'total_profit_amount' => 400,
            'has_stock_warning' => true,
            'has_cost_warning' => false,
        ]);

        return [$user, $sale];
    }
}
