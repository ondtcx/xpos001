<?php

namespace App\Support\Reports\Pdf;

use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Support\Reports\ReportDateRange;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReceivablesPdfExporter
{
    public function download(ReportDateRange $range): Response
    {
        $receivables = Receivable::query()
            ->with(['customer', 'sale.creator'])
            ->whereBetween('opened_at', [$range->start, $range->end])
            ->latest('opened_at')
            ->get();

        $payments = ReceivablePayment::query()
            ->with(['receivable.customer', 'receivable.sale', 'creator'])
            ->whereBetween('paid_at', [$range->start, $range->end])
            ->latest('paid_at')
            ->get();

        return Pdf::loadView('reports.pdf.receivables', [
            'range' => $range,
            'receivables' => $receivables,
            'payments' => $payments,
        ])->setPaper('a4', 'portrait')
            ->download(sprintf('cobranza_%s_%s.pdf', $range->start->format('Ymd'), $range->end->format('Ymd')));
    }
}
