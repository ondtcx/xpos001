<?php

namespace App\Support\Reports\Csv;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Support\Money;
use App\Support\Reports\ReportDateRange;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchasesCsvExporter
{
    public function __construct(private readonly CsvStreamDownloader $downloader) {}

    public function downloadSummary(ReportDateRange $range): StreamedResponse
    {
        $purchases = Purchase::query()
            ->with(['supplier', 'creator', 'voider', 'lots'])
            ->whereBetween('purchased_at', [$range->start, $range->end])
            ->latest('purchased_at')
            ->get();

        $fileName = sprintf('compras_resumen_%s_%s.csv', $range->start->format('Ymd'), $range->end->format('Ymd'));

        return $this->downloader->download($fileName, [
            'compra_id', 'fecha', 'proveedor', 'usuario', 'modo', 'tipo_pago', 'estado', 'factura',
            'subtotal', 'descuento_global', 'impuestos_globales', 'costos_extra', 'total', 'lotes_creados',
            'motivo_anulacion', 'anulada_por', 'anulada_en', 'notas',
        ], function ($output) use ($purchases) {
            foreach ($purchases as $purchase) {
                fputcsv($output, [
                    $purchase->id,
                    optional($purchase->purchased_at)->format('Y-m-d H:i:s'),
                    $purchase->supplier?->name ?? '—',
                    $purchase->creator?->name ?? '—',
                    $purchase->isDetailed() ? 'detallada' : 'rapida',
                    $purchase->payment_type,
                    $purchase->isVoided() ? 'anulada' : 'confirmada',
                    $purchase->invoice_number,
                    Money::centsToDollars($purchase->subtotal_amount),
                    Money::centsToDollars($purchase->global_discount_amount),
                    Money::centsToDollars(($purchase->global_tax_iva_amount ?? 0) + ($purchase->global_tax_ice_amount ?? 0) + ($purchase->global_tax_other_amount ?? 0)),
                    Money::centsToDollars($purchase->extra_costs_amount),
                    Money::centsToDollars($purchase->total_amount),
                    $purchase->lots->count(),
                    $purchase->void_reason,
                    $purchase->voider?->name,
                    optional($purchase->voided_at)->format('Y-m-d H:i:s'),
                    $purchase->notes,
                ]);
            }
        });
    }

    public function downloadLines(ReportDateRange $range): StreamedResponse
    {
        $items = PurchaseItem::query()
            ->with(['purchase.supplier', 'purchase.creator', 'variant.product'])
            ->whereHas('purchase', fn ($query) => $query->whereBetween('purchased_at', [$range->start, $range->end]))
            ->orderBy('purchase_id')
            ->orderBy('id')
            ->get();

        $fileName = sprintf('compras_lineas_%s_%s.csv', $range->start->format('Ymd'), $range->end->format('Ymd'));

        return $this->downloader->download($fileName, [
            'compra_id', 'linea_id', 'fecha', 'proveedor', 'usuario', 'estado_compra', 'modo_compra',
            'producto', 'variante', 'tipo_linea', 'cantidad', 'bonificacion', 'total_recibido',
            'costo_unitario_base', 'subtotal_linea', 'descuento_linea', 'iva_linea', 'ice_linea',
            'otro_impuesto_linea', 'descuento_global_prorrateado', 'iva_global_prorrateado',
            'ice_global_prorrateado', 'otro_global_prorrateado', 'extras_prorrateados',
            'costo_unitario_final', 'costo_total', 'vencimiento', 'notas_linea', 'motivo_anulacion_compra',
        ], function ($output) use ($items) {
            foreach ($items as $item) {
                $purchase = $item->purchase;

                fputcsv($output, [
                    $purchase?->id,
                    $item->id,
                    optional($purchase?->purchased_at)->format('Y-m-d H:i:s'),
                    $purchase?->supplier?->name ?? '—',
                    $purchase?->creator?->name ?? '—',
                    $purchase?->isVoided() ? 'anulada' : 'confirmada',
                    $purchase?->isDetailed() ? 'detallada' : 'rapida',
                    $item->variant?->product?->name,
                    $item->variant?->name,
                    $item->isBonusLine() ? 'bonificacion' : 'normal',
                    number_format((float) $item->quantity, 3, '.', ''),
                    number_format((float) $item->bonus_quantity, 3, '.', ''),
                    $item->receivedQuantity(),
                    Money::centsToDollars($item->unit_cost_base_amount ?? 0),
                    Money::centsToDollars($item->line_subtotal_amount ?? 0),
                    Money::centsToDollars($item->line_discount_amount ?? 0),
                    Money::centsToDollars(($item->tax_iva_amount ?? 0) + ($item->tax_vat_amount ?? 0)),
                    Money::centsToDollars(($item->tax_ice_amount ?? 0) + ($item->tax_fixed_amount ?? 0)),
                    Money::centsToDollars($item->tax_other_amount ?? 0),
                    Money::centsToDollars($item->allocated_global_discount_amount ?? 0),
                    Money::centsToDollars($item->allocated_global_tax_iva_amount ?? 0),
                    Money::centsToDollars($item->allocated_global_tax_ice_amount ?? 0),
                    Money::centsToDollars($item->allocated_global_tax_other_amount ?? 0),
                    Money::centsToDollars($item->allocated_extra_costs_amount ?? 0),
                    Money::centsToDollars($item->unit_cost_final_amount ?? 0),
                    Money::centsToDollars($item->total_cost_amount ?? 0),
                    optional($item->expiration_date)->format('Y-m-d'),
                    $item->notes,
                    $purchase?->void_reason,
                ]);
            }
        });
    }
}
