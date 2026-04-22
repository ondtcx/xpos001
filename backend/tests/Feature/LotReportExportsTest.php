<?php

namespace Tests\Feature;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LotReportExportsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_exports_current_lots_csv_with_operational_snapshot_columns(): void
    {
        [$user, $lot] = $this->seedLotExportScenario();

        $response = $this->actingAs($user)->get(route('reports.export.lots-csv', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('lote_id,fecha_recepcion,producto,variante,origen_tipo,origen_id,cantidad_inicial,cantidad_disponible,bonificacion,costo_final_unitario,precio_sugerido_venta,estimado,vencimiento,estado', $content);
        $this->assertStringContainsString((string) $lot->id, $content);
        $this->assertStringContainsString('Producto Lote Exporta', $content);
        $this->assertStringContainsString('purchase', $content);
        $this->assertStringContainsString('12.000,9.000,2.000', $content);
        $this->assertStringContainsString('1.88', $content);
    }

    #[Test]
    public function it_exports_lot_movements_csv_for_all_period_activity(): void
    {
        [$user, $lot, $movement] = $this->seedLotExportScenario();

        $response = $this->actingAs($user)->get(route('reports.export.lot-movements-csv', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('movimiento_id,fecha_movimiento,lote_id,producto,variante,tipo_movimiento,cantidad,costo_unitario,referencia_tipo,referencia_id,notas', $content);
        $this->assertStringContainsString((string) $movement->id, $content);
        $this->assertStringContainsString((string) $lot->id, $content);
        $this->assertStringContainsString('sale_output', $content);
        $this->assertStringContainsString('-3.000', $content);
        $this->assertStringContainsString('Consumo exportable', $content);
    }

    private function seedLotExportScenario(): array
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Categoría Lote Exporta', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'Marca Lote Exporta', 'is_active' => true]);
        $baseUnit = BaseUnit::query()->create(['name' => 'Unidad', 'symbol' => 'u']);

        $product = Product::query()->create([
            'name' => 'Producto Lote Exporta',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Variante Lote Exporta',
            'base_unit_id' => $baseUnit->id,
            'tracks_expiration' => true,
            'is_returnable' => false,
            'is_active' => true,
        ]);

        $lot = InventoryLot::query()->create([
            'variant_id' => $variant->id,
            'origin_type' => 'purchase',
            'origin_id' => 77,
            'received_at' => now()->subMinutes(25),
            'expiration_date' => now()->addMonths(2)->toDateString(),
            'initial_quantity' => 12,
            'available_quantity' => 9,
            'bonus_quantity' => 2,
            'unit_cost_final_amount' => 188,
            'suggested_sale_price_amount' => 300,
            'is_estimated' => false,
            'status' => 'active',
        ]);

        $movement = InventoryMovement::query()->create([
            'variant_id' => $variant->id,
            'lot_id' => $lot->id,
            'movement_type' => 'sale_output',
            'quantity' => -3,
            'unit_cost_amount' => 188,
            'reference_type' => 'sale',
            'reference_id' => 501,
            'movement_at' => now()->subMinutes(5),
            'notes' => 'Consumo exportable',
            'created_by' => $user->id,
        ]);

        return [$user, $lot, $movement];
    }
}
