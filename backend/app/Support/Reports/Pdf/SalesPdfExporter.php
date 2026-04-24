<?php

namespace App\Support\Reports\Pdf;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\Reports\ReportDateRange;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class SalesPdfExporter
{
    public function download(ReportDateRange $range): Response
    {
        $sales = Sale::query()
            ->with(['customer', 'creator', 'voider', 'items'])
            ->whereBetween('sold_at', [$range->start, $range->end])
            ->latest('sold_at')
            ->get();

        $items = SaleItem::query()
            ->with(['sale.customer', 'sale.creator', 'variant.product'])
            ->whereHas('sale', fn ($query) => $query->whereBetween('sold_at', [$range->start, $range->end]))
            ->orderBy('sale_id')
            ->orderBy('id')
            ->get();

        return Pdf::loadView('reports.pdf.sales', [
            'range' => $range,
            'sales' => $sales,
            'items' => $items,
        ])->setPaper('a4', 'landscape')
            ->download(sprintf('ventas_%s_%s.pdf', $range->start->format('Ymd'), $range->end->format('Ymd')));
    }
}
