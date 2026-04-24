<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\Reports\Csv\ExecutiveReportCsvExporter;
use App\Support\Reports\Csv\LotsCsvExporter;
use App\Support\Reports\Csv\PurchasesCsvExporter;
use App\Support\Reports\Csv\ReceivablesCsvExporter;
use App\Support\Reports\Csv\SalesCsvExporter;
use App\Support\Reports\ReportDateRange;
use App\Support\Reports\Pdf\PurchasesPdfExporter;
use App\Support\Reports\Pdf\ReceivablesPdfExporter;
use App\Support\Reports\Pdf\SalesPdfExporter;
use App\Support\Reports\Xlsx\PurchasesXlsxExporter;
use App\Support\Reports\Xlsx\ReceivablesXlsxExporter;
use App\Support\Reports\Xlsx\SalesXlsxExporter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    private const CASH_OPERATIONAL_MOVEMENT_TYPES = [
        'sale_payment',
        'receivable_payment',
    ];

    private const CASH_REVERSAL_MOVEMENT_TYPES = [
        'sale_payment_reversal',
        'receivable_payment_reversal',
    ];

    public function index(Request $request): View
    {
        return view('reports.index', $this->buildReportData($request));
    }

    public function print(Request $request): View
    {
        return view('reports.print', $this->buildReportData($request));
    }

    public function exportCsv(Request $request, ExecutiveReportCsvExporter $exporter): StreamedResponse
    {
        $range = ReportDateRange::fromRequest($request);

        return $exporter->download($range, $this->buildReportData($request));
    }

    public function exportSalesSummaryCsv(Request $request, SalesCsvExporter $exporter): StreamedResponse
    {
        return $exporter->downloadSummary(ReportDateRange::fromRequest($request));
    }

    public function exportSalesLinesCsv(Request $request, SalesCsvExporter $exporter): StreamedResponse
    {
        return $exporter->downloadLines(ReportDateRange::fromRequest($request));
    }

    public function exportSalesXlsx(Request $request, SalesXlsxExporter $exporter): StreamedResponse
    {
        return $exporter->download(ReportDateRange::fromRequest($request));
    }

    public function exportSalesPdf(Request $request, SalesPdfExporter $exporter): Response
    {
        return $exporter->download(ReportDateRange::fromRequest($request));
    }

    public function exportPurchasesSummaryCsv(Request $request, PurchasesCsvExporter $exporter): StreamedResponse
    {
        return $exporter->downloadSummary(ReportDateRange::fromRequest($request));
    }

    public function exportPurchasesLinesCsv(Request $request, PurchasesCsvExporter $exporter): StreamedResponse
    {
        return $exporter->downloadLines(ReportDateRange::fromRequest($request));
    }

    public function exportPurchasesXlsx(Request $request, PurchasesXlsxExporter $exporter): StreamedResponse
    {
        return $exporter->download(ReportDateRange::fromRequest($request));
    }

    public function exportPurchasesPdf(Request $request, PurchasesPdfExporter $exporter): Response
    {
        return $exporter->download(ReportDateRange::fromRequest($request));
    }

    public function exportReceivablesCsv(Request $request, ReceivablesCsvExporter $exporter): StreamedResponse
    {
        return $exporter->downloadReceivables(ReportDateRange::fromRequest($request));
    }

    public function exportReceivablePaymentsCsv(Request $request, ReceivablesCsvExporter $exporter): StreamedResponse
    {
        return $exporter->downloadPayments(ReportDateRange::fromRequest($request));
    }

    public function exportReceivablesXlsx(Request $request, ReceivablesXlsxExporter $exporter): StreamedResponse
    {
        return $exporter->download(ReportDateRange::fromRequest($request));
    }

    public function exportReceivablesPdf(Request $request, ReceivablesPdfExporter $exporter): Response
    {
        return $exporter->download(ReportDateRange::fromRequest($request));
    }

    public function exportLotsCsv(Request $request, LotsCsvExporter $exporter): StreamedResponse
    {
        return $exporter->downloadLots(ReportDateRange::fromRequest($request));
    }

    public function exportLotMovementsCsv(Request $request, LotsCsvExporter $exporter): StreamedResponse
    {
        return $exporter->downloadMovements(ReportDateRange::fromRequest($request));
    }

    private function buildReportData(Request $request): array
    {
        $range = ReportDateRange::fromRequest($request);
        $start = $range->start;
        $end = $range->end;

        $salesInRange = Sale::query()->whereBetween('sold_at', [$start, $end]);
        $confirmedSalesInRange = (clone $salesInRange)->where('status', Sale::STATUS_CONFIRMED);
        $voidedSalesInRange = (clone $salesInRange)->where('status', Sale::STATUS_VOIDED);
        $purchasesInRange = Purchase::query()->whereBetween('purchased_at', [$start, $end]);
        $confirmedPurchasesInRange = (clone $purchasesInRange)->where('status', Purchase::STATUS_CONFIRMED);
        $voidedPurchasesInRange = (clone $purchasesInRange)->where('status', Purchase::STATUS_VOIDED);
        $receivablePaymentsInRange = ReceivablePayment::query()->whereBetween('paid_at', [$start, $end]);
        $activeReceivablePaymentsInRange = (clone $receivablePaymentsInRange)->where('is_reversed', false);
        $reversedReceivablePaymentsInRange = (clone $receivablePaymentsInRange)->where('is_reversed', true);
        $cashSessionsInRange = CashSession::query()->whereBetween('opened_at', [$start, $end]);
        $cashMovementsInRange = CashMovement::query()->whereBetween('created_at', [$start, $end]);

        $salesGrossTotal = (clone $salesInRange)->sum('total_amount');
        $salesVoidedTotal = (clone $voidedSalesInRange)->sum('total_amount');
        $salesNetTotal = (clone $confirmedSalesInRange)->sum('total_amount');
        $salesGrossPaid = (clone $salesInRange)->sum('paid_amount');
        $salesVoidedPaid = (clone $voidedSalesInRange)->sum('paid_amount');
        $salesNetPaid = (clone $confirmedSalesInRange)->sum('paid_amount');
        $salesGrossCredit = (clone $salesInRange)->sum('credit_amount');
        $salesVoidedCredit = (clone $voidedSalesInRange)->sum('credit_amount');
        $salesNetCredit = (clone $confirmedSalesInRange)->sum('credit_amount');

        $profitReliableTotal = SaleItem::query()
            ->whereHas('sale', fn ($query) => $query->whereBetween('sold_at', [$start, $end])->where('status', Sale::STATUS_CONFIRMED))
            ->where('has_cost_warning', false)
            ->where('has_stock_warning', false)
            ->sum('total_profit_amount');
        $profitWarningTotal = SaleItem::query()
            ->whereHas('sale', fn ($query) => $query->whereBetween('sold_at', [$start, $end])->where('status', Sale::STATUS_CONFIRMED))
            ->where(function ($query) {
                $query->where('has_cost_warning', true)
                    ->orWhere('has_stock_warning', true);
            })
            ->sum('total_profit_amount');
        $profitVoidedTotal = SaleItem::query()
            ->whereHas('sale', fn ($query) => $query->whereBetween('sold_at', [$start, $end])->where('status', Sale::STATUS_VOIDED))
            ->sum('total_profit_amount');

        $purchasesGrossTotal = (clone $purchasesInRange)->sum('total_amount');
        $purchasesVoidedTotal = (clone $voidedPurchasesInRange)->sum('total_amount');
        $purchasesNetTotal = (clone $confirmedPurchasesInRange)->sum('total_amount');

        $stockCurrent = InventoryLot::query()->sum('available_quantity');
        $stockByVariant = InventoryLot::query()
            ->selectRaw('variant_id, SUM(available_quantity) as total_available')
            ->with('variant.product')
            ->groupBy('variant_id')
            ->orderBy('total_available')
            ->get();

        $lowStock = $stockByVariant
            ->filter(fn ($row) => (float) $row->total_available <= 5)
            ->values();

        $receivablesOpen = Receivable::query()->with('customer')->where('status', 'open')->orderByDesc('opened_at')->get();
        $receivablesPendingTotal = $receivablesOpen->sum('pending_amount');
        $receivedPaymentsGrossTotal = (clone $receivablePaymentsInRange)->sum('amount');
        $receivedPaymentsReversedTotal = (clone $reversedReceivablePaymentsInRange)->sum('amount');
        $receivedPaymentsNetTotal = (clone $activeReceivablePaymentsInRange)->sum('amount');

        $cashClosures = $cashSessionsInRange->where('status', 'closed')->latest('closed_at')->get();

        $marginByProduct = SaleItem::query()
            ->selectRaw('variant_id, SUM(subtotal_amount) as total_sales_amount, SUM(total_cost_amount) as total_cost_amount, SUM(total_profit_amount) as total_profit_amount')
            ->whereHas('sale', fn ($query) => $query->whereBetween('sold_at', [$start, $end])->where('status', Sale::STATUS_CONFIRMED))
            ->where('has_cost_warning', false)
            ->where('has_stock_warning', false)
            ->with('variant.product')
            ->groupBy('variant_id')
            ->orderByDesc('total_profit_amount')
            ->get();

        $marginExcludedByWarnings = SaleItem::query()
            ->selectRaw('SUM(total_profit_amount) as total_profit_amount')
            ->whereHas('sale', fn ($query) => $query->whereBetween('sold_at', [$start, $end])->where('status', Sale::STATUS_CONFIRMED))
            ->where(function ($query) {
                $query->where('has_cost_warning', true)
                    ->orWhere('has_stock_warning', true);
            })
            ->value('total_profit_amount') ?? 0;

        $marginVoided = SaleItem::query()
            ->selectRaw('SUM(total_profit_amount) as total_profit_amount')
            ->whereHas('sale', fn ($query) => $query->whereBetween('sold_at', [$start, $end])->where('status', Sale::STATUS_VOIDED))
            ->value('total_profit_amount') ?? 0;

        $purchasesBySupplier = Purchase::query()
            ->selectRaw('supplier_id, COUNT(*) as purchases_count, SUM(total_amount) as total_amount')
            ->with('supplier')
            ->whereBetween('purchased_at', [$start, $end])
            ->where('status', Purchase::STATUS_CONFIRMED)
            ->groupBy('supplier_id')
            ->orderByDesc('total_amount')
            ->get();

        $recentSales = Sale::query()
            ->with(['customer', 'creator'])
            ->whereBetween('sold_at', [$start, $end])
            ->latest('sold_at')
            ->limit(8)
            ->get();

        $recentPurchases = Purchase::query()
            ->with(['supplier', 'creator'])
            ->whereBetween('purchased_at', [$start, $end])
            ->latest('purchased_at')
            ->limit(8)
            ->get();

        $lotMovements = InventoryLot::query()
            ->with(['variant.product', 'movements' => fn ($query) => $query->latest('movement_at')])
            ->latest('received_at')
            ->limit(50)
            ->get();

        $cashSummary = (clone $cashMovementsInRange)
            ->selectRaw('payment_method, movement_type, SUM(amount) as total_amount')
            ->groupBy('payment_method', 'movement_type')
            ->get();

        $cashOperationalTotal = (clone $cashMovementsInRange)
            ->whereIn('movement_type', self::CASH_OPERATIONAL_MOVEMENT_TYPES)
            ->sum('amount');
        $cashReversalTotal = (clone $cashMovementsInRange)
            ->whereIn('movement_type', self::CASH_REVERSAL_MOVEMENT_TYPES)
            ->sum('amount');
        $cashManualTotal = (clone $cashMovementsInRange)
            ->whereNotIn('movement_type', array_merge(self::CASH_OPERATIONAL_MOVEMENT_TYPES, self::CASH_REVERSAL_MOVEMENT_TYPES))
            ->sum('amount');
        $cashNetTotal = (clone $cashMovementsInRange)->sum('amount');

        return compact(
            'start',
            'end',
            'salesGrossTotal',
            'salesVoidedTotal',
            'salesNetTotal',
            'salesGrossPaid',
            'salesVoidedPaid',
            'salesNetPaid',
            'salesGrossCredit',
            'salesVoidedCredit',
            'salesNetCredit',
            'profitReliableTotal',
            'profitWarningTotal',
            'profitVoidedTotal',
            'purchasesGrossTotal',
            'purchasesVoidedTotal',
            'purchasesNetTotal',
            'stockCurrent',
            'lowStock',
            'receivablesOpen',
            'receivablesPendingTotal',
            'receivedPaymentsGrossTotal',
            'receivedPaymentsReversedTotal',
            'receivedPaymentsNetTotal',
            'cashClosures',
            'marginByProduct',
            'marginExcludedByWarnings',
            'marginVoided',
            'purchasesBySupplier',
            'recentSales',
            'recentPurchases',
            'lotMovements',
            'cashSummary',
            'cashOperationalTotal',
            'cashReversalTotal',
            'cashManualTotal',
            'cashNetTotal',
        );
    }

}
