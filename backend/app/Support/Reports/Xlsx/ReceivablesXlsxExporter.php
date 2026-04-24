<?php

namespace App\Support\Reports\Xlsx;

use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Support\Money;
use App\Support\Reports\ReportDateRange;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceivablesXlsxExporter
{
    public function __construct(private readonly XlsxWorkbookDownloader $downloader) {}

    public function download(ReportDateRange $range): StreamedResponse
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

        return $this->downloader->download(
            sprintf('cobranza_%s_%s.xlsx', $range->start->format('Ymd'), $range->end->format('Ymd')),
            [
                [
                    'name' => 'Fiados cuentas',
                    'headings' => [
                        'cuenta_id', 'fecha_apertura', 'cliente', 'venta_id', 'usuario_venta', 'monto_original',
                        'saldo_pendiente', 'estado', 'cancelada_por_anulacion', 'cancelada_en', 'motivo_cancelacion',
                    ],
                    'rows' => $receivables->map(fn (Receivable $receivable) => [
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
                    ])->all(),
                    'money_columns' => [5, 6],
                ],
                [
                    'name' => 'Fiados abonos',
                    'headings' => [
                        'abono_id', 'cuenta_id', 'venta_id', 'fecha_abono', 'cliente', 'usuario_registro',
                        'metodo_pago', 'monto', 'esta_revertido', 'fecha_reversa', 'motivo_reversa', 'notas',
                    ],
                    'rows' => $payments->map(fn (ReceivablePayment $payment) => [
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
                    ])->all(),
                    'money_columns' => [7],
                ],
            ],
        );
    }
}
