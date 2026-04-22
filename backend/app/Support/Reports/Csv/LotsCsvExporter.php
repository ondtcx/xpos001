<?php

namespace App\Support\Reports\Csv;

use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Support\Money;
use App\Support\Reports\ReportDateRange;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LotsCsvExporter
{
    public function __construct(private readonly CsvStreamDownloader $downloader) {}

    public function downloadLots(ReportDateRange $range): StreamedResponse
    {
        $lots = InventoryLot::query()
            ->with(['variant.product'])
            ->whereBetween('received_at', [$range->start, $range->end])
            ->latest('received_at')
            ->get();

        $fileName = sprintf('lotes_%s_%s.csv', $range->start->format('Ymd'), $range->end->format('Ymd'));

        return $this->downloader->download($fileName, [
            'lote_id', 'fecha_recepcion', 'producto', 'variante', 'origen_tipo', 'origen_id',
            'cantidad_inicial', 'cantidad_disponible', 'bonificacion', 'costo_final_unitario',
            'precio_sugerido_venta', 'estimado', 'vencimiento', 'estado',
        ], function ($output) use ($lots) {
            foreach ($lots as $lot) {
                fputcsv($output, [
                    $lot->id,
                    optional($lot->received_at)->format('Y-m-d H:i:s'),
                    $lot->variant?->product?->name,
                    $lot->variant?->name,
                    $lot->origin_type,
                    $lot->origin_id,
                    number_format((float) $lot->initial_quantity, 3, '.', ''),
                    number_format((float) $lot->available_quantity, 3, '.', ''),
                    number_format((float) $lot->bonus_quantity, 3, '.', ''),
                    Money::centsToDollars($lot->unit_cost_final_amount),
                    Money::centsToDollars($lot->suggested_sale_price_amount ?? 0),
                    $lot->is_estimated ? 'si' : 'no',
                    optional($lot->expiration_date)->format('Y-m-d'),
                    $lot->status,
                ]);
            }
        });
    }

    public function downloadMovements(ReportDateRange $range): StreamedResponse
    {
        $movements = InventoryMovement::query()
            ->with(['lot.variant.product'])
            ->whereBetween('movement_at', [$range->start, $range->end])
            ->latest('movement_at')
            ->get();

        $fileName = sprintf('lote_movimientos_%s_%s.csv', $range->start->format('Ymd'), $range->end->format('Ymd'));

        return $this->downloader->download($fileName, [
            'movimiento_id', 'fecha_movimiento', 'lote_id', 'producto', 'variante', 'tipo_movimiento',
            'cantidad', 'costo_unitario', 'referencia_tipo', 'referencia_id', 'notas',
        ], function ($output) use ($movements) {
            foreach ($movements as $movement) {
                fputcsv($output, [
                    $movement->id,
                    optional($movement->movement_at)->format('Y-m-d H:i:s'),
                    $movement->lot_id,
                    $movement->lot?->variant?->product?->name,
                    $movement->lot?->variant?->name,
                    $movement->movement_type,
                    number_format((float) $movement->quantity, 3, '.', ''),
                    Money::centsToDollars($movement->unit_cost_amount ?? 0),
                    $movement->reference_type,
                    $movement->reference_id,
                    $movement->notes,
                ]);
            }
        });
    }
}
