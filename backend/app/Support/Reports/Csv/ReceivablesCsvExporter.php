<?php

namespace App\Support\Reports\Csv;

use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Support\Money;
use App\Support\Reports\ReportDateRange;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceivablesCsvExporter
{
    public function __construct(private readonly CsvStreamDownloader $downloader) {}

    public function downloadReceivables(ReportDateRange $range): StreamedResponse
    {
        $receivables = Receivable::query()
            ->with(['customer', 'sale.creator'])
            ->whereBetween('opened_at', [$range->start, $range->end])
            ->latest('opened_at')
            ->get();

        $fileName = sprintf('fiados_%s_%s.csv', $range->start->format('Ymd'), $range->end->format('Ymd'));

        return $this->downloader->download($fileName, [
            'cuenta_id', 'fecha_apertura', 'cliente', 'venta_id', 'usuario_venta', 'monto_original',
            'saldo_pendiente', 'estado', 'cancelada_por_anulacion', 'cancelada_en', 'motivo_cancelacion',
        ], function ($output) use ($receivables) {
            foreach ($receivables as $receivable) {
                fputcsv($output, [
                    $receivable->id,
                    optional($receivable->opened_at)->format('Y-m-d H:i:s'),
                    $receivable->customer?->name ?? '—',
                    $receivable->sale?->id,
                    $receivable->sale?->creator?->name ?? '—',
                    Money::centsToDollars($receivable->original_amount),
                    Money::centsToDollars($receivable->pending_amount),
                    $receivable->status,
                    $receivable->isCancelled() ? 'si' : 'no',
                    optional($receivable->cancelled_at)->format('Y-m-d H:i:s'),
                    $receivable->cancel_reason,
                ]);
            }
        });
    }

    public function downloadPayments(ReportDateRange $range): StreamedResponse
    {
        $payments = ReceivablePayment::query()
            ->with(['receivable.customer', 'receivable.sale', 'creator'])
            ->whereBetween('paid_at', [$range->start, $range->end])
            ->latest('paid_at')
            ->get();

        $fileName = sprintf('abonos_%s_%s.csv', $range->start->format('Ymd'), $range->end->format('Ymd'));

        return $this->downloader->download($fileName, [
            'abono_id', 'cuenta_id', 'venta_id', 'fecha_abono', 'cliente', 'usuario_registro',
            'metodo_pago', 'monto', 'esta_revertido', 'fecha_reversa', 'motivo_reversa', 'notas',
        ], function ($output) use ($payments) {
            foreach ($payments as $payment) {
                fputcsv($output, [
                    $payment->id,
                    $payment->receivable_id,
                    $payment->receivable?->sale?->id,
                    optional($payment->paid_at)->format('Y-m-d H:i:s'),
                    $payment->receivable?->customer?->name ?? '—',
                    $payment->creator?->name ?? '—',
                    $payment->payment_method,
                    Money::centsToDollars($payment->amount),
                    $payment->is_reversed ? 'si' : 'no',
                    optional($payment->reversed_at)->format('Y-m-d H:i:s'),
                    $payment->reversal_reason,
                    $payment->notes,
                ]);
            }
        });
    }
}
