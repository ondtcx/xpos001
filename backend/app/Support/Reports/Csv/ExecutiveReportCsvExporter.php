<?php

namespace App\Support\Reports\Csv;

use App\Support\Money;
use App\Support\Reports\ReportDateRange;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExecutiveReportCsvExporter
{
    public function __construct(private readonly CsvStreamDownloader $downloader) {}

    public function download(ReportDateRange $range, array $data): StreamedResponse
    {
        $fileName = sprintf('reportes_%s_%s.csv', $range->start->format('Ymd'), $range->end->format('Ymd'));

        return $this->downloader->download($fileName, ['Reporte', 'Valor'], function ($output) use ($data) {
            fputcsv($output, ['Ventas brutas', Money::centsToDollars($data['salesGrossTotal'])]);
            fputcsv($output, ['Ventas anuladas', Money::centsToDollars($data['salesVoidedTotal'])]);
            fputcsv($output, ['Ventas netas', Money::centsToDollars($data['salesNetTotal'])]);
            fputcsv($output, ['Cobrado neto por ventas', Money::centsToDollars($data['salesNetPaid'])]);
            fputcsv($output, ['Fiado neto de ventas', Money::centsToDollars($data['salesNetCredit'])]);
            fputcsv($output, ['Utilidad confiable', Money::centsToDollars($data['profitReliableTotal'])]);
            fputcsv($output, ['Utilidad excluida por warnings', Money::centsToDollars($data['profitWarningTotal'])]);
            fputcsv($output, ['Utilidad anulada', Money::centsToDollars($data['profitVoidedTotal'])]);
            fputcsv($output, ['Compras brutas', Money::centsToDollars($data['purchasesGrossTotal'])]);
            fputcsv($output, ['Compras anuladas', Money::centsToDollars($data['purchasesVoidedTotal'])]);
            fputcsv($output, ['Compras netas', Money::centsToDollars($data['purchasesNetTotal'])]);
            fputcsv($output, ['Stock total', number_format((float) $data['stockCurrent'], 3, '.', '')]);
            fputcsv($output, ['Fiados pendientes', Money::centsToDollars($data['receivablesPendingTotal'])]);
            fputcsv($output, ['Abonos brutos', Money::centsToDollars($data['receivedPaymentsGrossTotal'])]);
            fputcsv($output, ['Abonos revertidos', Money::centsToDollars($data['receivedPaymentsReversedTotal'])]);
            fputcsv($output, ['Abonos netos', Money::centsToDollars($data['receivedPaymentsNetTotal'])]);
            fputcsv($output, ['Caja operativa', Money::centsToDollars($data['cashOperationalTotal'])]);
            fputcsv($output, ['Caja reversas', Money::centsToDollars($data['cashReversalTotal'])]);
            fputcsv($output, ['Caja movimientos manuales', Money::centsToDollars($data['cashManualTotal'])]);
            fputcsv($output, ['Caja neta', Money::centsToDollars($data['cashNetTotal'])]);
            fputcsv($output, []);

            fputcsv($output, ['Productos por agotarse']);
            fputcsv($output, ['Producto', 'Variante', 'Disponible']);
            foreach ($data['lowStock'] as $row) {
                fputcsv($output, [
                    $row->variant->product->name,
                    $row->variant->name,
                    number_format((float) $row->total_available, 3, '.', ''),
                ]);
            }
            fputcsv($output, []);

            fputcsv($output, ['Compras por proveedor']);
            fputcsv($output, ['Proveedor', 'Compras', 'Total']);
            foreach ($data['purchasesBySupplier'] as $row) {
                fputcsv($output, [
                    $row->supplier?->name ?? 'Sin proveedor',
                    $row->purchases_count,
                    Money::centsToDollars((int) $row->total_amount),
                ]);
            }
            fputcsv($output, []);

            fputcsv($output, ['Margen por producto']);
            fputcsv($output, ['Producto', 'Ventas', 'Costo', 'Utilidad']);
            foreach ($data['marginByProduct'] as $row) {
                fputcsv($output, [
                    $row->variant->product->name . ' — ' . $row->variant->name,
                    Money::centsToDollars((int) $row->total_sales_amount),
                    Money::centsToDollars((int) $row->total_cost_amount),
                    Money::centsToDollars((int) $row->total_profit_amount),
                ]);
            }
        });
    }
}
