<?php

namespace App\Support\Reports\Csv;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\Money;
use App\Support\Reports\ReportDateRange;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesCsvExporter
{
    public function __construct(private readonly CsvStreamDownloader $downloader) {}

    public function downloadSummary(ReportDateRange $range): StreamedResponse
    {
        $sales = Sale::query()
            ->with(['customer', 'creator', 'voider', 'items'])
            ->whereBetween('sold_at', [$range->start, $range->end])
            ->latest('sold_at')
            ->get();

        $fileName = sprintf('ventas_resumen_%s_%s.csv', $range->start->format('Ymd'), $range->end->format('Ymd'));

        return $this->downloader->download($fileName, [
            'venta_id', 'fecha', 'cliente', 'usuario', 'estado', 'total', 'pagado', 'fiado', 'items',
            'tiene_override_precio', 'tiene_warning_stock', 'tiene_warning_costo', 'motivo_anulacion',
            'anulada_por', 'anulada_en', 'notas',
        ], function ($output) use ($sales) {
            foreach ($sales as $sale) {
                fputcsv($output, [
                    $sale->id,
                    optional($sale->sold_at)->format('Y-m-d H:i:s'),
                    $sale->customer?->name ?? 'Venta anónima',
                    $sale->creator?->name ?? '—',
                    $sale->isVoided() ? 'anulada' : ($sale->credit_amount > 0 ? 'con_saldo_pendiente' : 'cobrada'),
                    Money::centsToDollars($sale->total_amount),
                    Money::centsToDollars($sale->paid_amount),
                    Money::centsToDollars($sale->credit_amount),
                    $sale->items->count(),
                    $sale->items->contains(fn ($item) => $item->has_manual_price_override) ? 'si' : 'no',
                    $sale->items->contains(fn ($item) => $item->has_stock_warning) ? 'si' : 'no',
                    $sale->items->contains(fn ($item) => $item->has_cost_warning) ? 'si' : 'no',
                    $sale->void_reason,
                    $sale->voider?->name,
                    optional($sale->voided_at)->format('Y-m-d H:i:s'),
                    $sale->notes,
                ]);
            }
        });
    }

    public function downloadLines(ReportDateRange $range): StreamedResponse
    {
        $items = SaleItem::query()
            ->with(['sale.customer', 'sale.creator', 'variant.product'])
            ->whereHas('sale', fn ($query) => $query->whereBetween('sold_at', [$range->start, $range->end]))
            ->orderBy('sale_id')
            ->orderBy('id')
            ->get();

        $fileName = sprintf('ventas_lineas_%s_%s.csv', $range->start->format('Ymd'), $range->end->format('Ymd'));

        return $this->downloader->download($fileName, [
            'venta_id', 'linea_id', 'fecha', 'cliente', 'usuario', 'estado_venta', 'producto', 'variante',
            'descripcion', 'cantidad', 'precio_original', 'precio_aplicado', 'override_precio',
            'motivo_override', 'warning_stock', 'warning_costo', 'costo_total', 'utilidad_total',
            'motivo_anulacion_venta',
        ], function ($output) use ($items) {
            foreach ($items as $item) {
                $sale = $item->sale;

                fputcsv($output, [
                    $sale?->id,
                    $item->id,
                    optional($sale?->sold_at)->format('Y-m-d H:i:s'),
                    $sale?->customer?->name ?? 'Venta anónima',
                    $sale?->creator?->name ?? '—',
                    $sale?->isVoided() ? 'anulada' : ($sale?->credit_amount > 0 ? 'con_saldo_pendiente' : 'cobrada'),
                    $item->variant?->product?->name,
                    $item->variant?->name,
                    $item->description_snapshot,
                    number_format((float) $item->quantity, 3, '.', ''),
                    Money::centsToDollars($item->original_unit_price_amount ?? $item->unit_price_amount),
                    Money::centsToDollars($item->unit_price_amount),
                    $item->has_manual_price_override ? 'si' : 'no',
                    $item->manual_price_reason,
                    $item->has_stock_warning ? 'si' : 'no',
                    $item->has_cost_warning ? 'si' : 'no',
                    Money::centsToDollars((int) $item->total_cost_amount),
                    Money::centsToDollars((int) $item->total_profit_amount),
                    $sale?->void_reason,
                ]);
            }
        });
    }
}
