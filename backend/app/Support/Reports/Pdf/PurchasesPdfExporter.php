<?php

namespace App\Support\Reports\Pdf;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Support\Reports\ReportDateRange;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PurchasesPdfExporter
{
    public function download(ReportDateRange $range): Response
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

        return Pdf::loadView('reports.pdf.purchases', [
            'range' => $range,
            'purchases' => $purchases,
            'items' => $items,
        ])->setPaper('a4', 'landscape')
            ->download(sprintf('compras_%s_%s.pdf', $range->start->format('Ymd'), $range->end->format('Ymd')));
    }
}
