<?php

namespace App\Support\Reports\Xlsx;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Support\Money;
use App\Support\Reports\ReportDateRange;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchasesXlsxExporter
{
    public function __construct(private readonly XlsxWorkbookDownloader $downloader) {}

    public function download(ReportDateRange $range): StreamedResponse
    {
        $purchases = Purchase::query()
            ->with(['supplier', 'creator', 'voider', 'lots'])
            ->whereBetween('purchased_at', [$range->start, $range->end])
            ->latest('purchased_at')
            ->get();

        $items = PurchaseItem::query()
            ->with(['purchase.supplier', 'purchase.creator', 'variant.product'])
            ->whereHas('purchase', fn ($query) => $query->whereBetween('purchased_at', [$range->start, $range->end]))
            ->orderBy('purchase_id')
            ->orderBy('id')
            ->get();

        return $this->downloader->download(
            sprintf('compras_%s_%s.xlsx', $range->start->format('Ymd'), $range->end->format('Ymd')),
            [
                [
                    'name' => 'Compras resumen',
                    'headings' => [
                        'compra_id', 'fecha', 'proveedor', 'usuario', 'modo', 'tipo_pago', 'estado', 'factura',
                        'subtotal', 'descuento_global', 'impuestos_globales', 'costos_extra', 'total', 'lotes_creados',
                        'motivo_anulacion', 'anulada_por', 'anulada_en', 'notas',
                    ],
                    'rows' => $purchases->map(fn (Purchase $purchase) => [
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
                    ])->all(),
                    'money_columns' => [8, 9, 10, 11, 12],
                ],
                [
                    'name' => 'Compras lineas',
                    'headings' => [
                        'compra_id', 'linea_id', 'fecha', 'proveedor', 'usuario', 'estado_compra', 'modo_compra',
                        'producto', 'variante', 'tipo_linea', 'cantidad', 'bonificacion', 'total_recibido',
                        'costo_unitario_base', 'subtotal_linea', 'descuento_linea', 'iva_linea', 'ice_linea',
                        'otro_impuesto_linea', 'descuento_global_prorrateado', 'iva_global_prorrateado',
                        'ice_global_prorrateado', 'otro_global_prorrateado', 'extras_prorrateados',
                        'costo_unitario_final', 'costo_total', 'vencimiento', 'notas_linea', 'motivo_anulacion_compra',
                    ],
                    'rows' => $items->map(function (PurchaseItem $item) {
                        $purchase = $item->purchase;

                        return [
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
                            (float) $item->quantity,
                            (float) $item->bonus_quantity,
                            (float) $item->receivedQuantity(),
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
                        ];
                    })->all(),
                    'money_columns' => [13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25],
                    'quantity_columns' => [10, 11, 12],
                ],
            ],
        );
    }
}
