<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseReportExportsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_exports_purchase_summary_csv_with_operational_columns(): void
    {
        [$user, $purchase] = $this->seedPurchaseExportScenario();

        $response = $this->actingAs($user)->get(route('reports.export.purchases-summary-csv', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('compra_id,fecha,proveedor,usuario,modo,tipo_pago,estado,factura,subtotal,descuento_global,impuestos_globales,costos_extra,total,lotes_creados,motivo_anulacion,anulada_por,anulada_en,notas', $content);
        $this->assertStringContainsString((string) $purchase->id, $content);
        $this->assertStringContainsString('Proveedor Exportación', $content);
        $this->assertStringContainsString('detallada', $content);
        $this->assertStringContainsString('transfer', $content);
        $this->assertStringContainsString('FAC-EXPORT-01', $content);
        $this->assertStringContainsString('Compra exportable', $content);
    }

    #[Test]
    public function it_exports_purchase_lines_csv_with_line_level_cost_allocations(): void
    {
        [$user, $purchase] = $this->seedPurchaseExportScenario();

        $response = $this->actingAs($user)->get(route('reports.export.purchases-lines-csv', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('compra_id,linea_id,fecha,proveedor,usuario,estado_compra,modo_compra,producto,variante,tipo_linea,cantidad,bonificacion,total_recibido,costo_unitario_base,subtotal_linea,descuento_linea,iva_linea,ice_linea,otro_impuesto_linea,descuento_global_prorrateado,iva_global_prorrateado,ice_global_prorrateado,otro_global_prorrateado,extras_prorrateados,costo_unitario_final,costo_total,vencimiento,notas_linea,motivo_anulacion_compra', $content);
        $this->assertStringContainsString((string) $purchase->id, $content);
        $this->assertStringContainsString('Producto Compra Exportación', $content);
        $this->assertStringContainsString('normal', $content);
        $this->assertStringContainsString('10.000,2.000,12.000', $content);
        $this->assertStringContainsString('Línea exportable', $content);
        $this->assertStringContainsString('1.88', $content);
    }

    private function seedPurchaseExportScenario(): array
    {
        $user = User::factory()->create(['name' => 'Usuario Compra Exporta']);
        $supplier = Supplier::query()->create(['name' => 'Proveedor Exportación', 'is_active' => true]);
        $category = Category::query()->create(['name' => 'Categoría Compra Exporta', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Marca Compra Exporta', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $product = Product::query()->create([
            'name' => 'Producto Compra Exportación',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Variante Compra Exportación',
            'base_unit_id' => $baseUnit->id,
            'tracks_expiration' => true,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        $purchase = Purchase::query()->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'FAC-EXPORT-01',
            'purchased_at' => now()->subMinutes(20),
            'payment_type' => 'transfer',
            'entry_mode' => Purchase::ENTRY_MODE_DETAILED,
            'subtotal_amount' => 2000,
            'global_discount_amount' => 100,
            'global_tax_iva_amount' => 200,
            'global_tax_other_amount' => 50,
            'extra_costs_amount' => 75,
            'total_amount' => 2225,
            'notes' => 'Compra exportable',
            'status' => Purchase::STATUS_CONFIRMED,
            'created_by' => $user->id,
        ]);

        PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'variant_id' => $variant->id,
            'line_type' => PurchaseItem::LINE_TYPE_NORMAL,
            'quantity' => 10,
            'bonus_quantity' => 2,
            'unit_cost_base_amount' => 200,
            'line_subtotal_amount' => 2000,
            'line_discount_amount' => 50,
            'tax_iva_amount' => 120,
            'tax_other_amount' => 20,
            'allocated_global_discount_amount' => 50,
            'allocated_global_tax_iva_amount' => 80,
            'allocated_global_tax_other_amount' => 30,
            'allocated_extra_costs_amount' => 75,
            'unit_cost_final_amount' => 188,
            'total_cost_amount' => 2255,
            'expiration_date' => now()->addMonths(3)->toDateString(),
            'notes' => 'Línea exportable',
        ]);

        return [$user, $purchase];
    }
}
