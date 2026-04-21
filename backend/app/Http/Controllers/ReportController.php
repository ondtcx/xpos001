<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\InventoryLot;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.index', $this->buildReportData($request));
    }

    public function print(Request $request): View
    {
        return view('reports.print', $this->buildReportData($request));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $data = $this->buildReportData($request);
        $fileName = sprintf('reportes_%s_%s.csv', $data['start']->format('Ymd'), $data['end']->format('Ymd'));

        return response()->streamDownload(function () use ($data) {
            $output = fopen('php://output', 'w');

            fputcsv($output, ['Reporte', 'Valor']);
            fputcsv($output, ['Ventas', Money::centsToDollars($data['salesTodayTotal'])]);
            fputcsv($output, ['Pagado', Money::centsToDollars($data['salesTodayPaid'])]);
            fputcsv($output, ['Fiado', Money::centsToDollars($data['salesTodayCredit'])]);
            fputcsv($output, ['Utilidad', Money::centsToDollars($data['profitToday'])]);
            fputcsv($output, ['Compras', Money::centsToDollars($data['purchasesTotal'])]);
            fputcsv($output, ['Stock total', number_format((float) $data['stockCurrent'], 3, '.', '')]);
            fputcsv($output, ['Fiados pendientes', Money::centsToDollars($data['receivablesPendingTotal'])]);
            fputcsv($output, ['Abonos recibidos', Money::centsToDollars($data['receivedPaymentsTotal'])]);
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

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildReportData(Request $request): array
    {
        $today = Carbon::today();
        $start = Carbon::parse($request->input('start_date', $today->toDateString()))->startOfDay();
        $end = Carbon::parse($request->input('end_date', $today->toDateString()))->endOfDay();

        $salesInRange = Sale::query()->whereBetween('sold_at', [$start, $end]);
        $purchasesInRange = Purchase::query()->whereBetween('purchased_at', [$start, $end]);
        $receivablePaymentsInRange = ReceivablePayment::query()->whereBetween('paid_at', [$start, $end]);
        $cashSessionsInRange = CashSession::query()->whereBetween('opened_at', [$start, $end]);

        $salesTodayTotal = (clone $salesInRange)->sum('total_amount');
        $salesTodayPaid = (clone $salesInRange)->sum('paid_amount');
        $salesTodayCredit = (clone $salesInRange)->sum('credit_amount');
        $profitToday = SaleItem::query()
            ->whereHas('sale', fn ($query) => $query->whereBetween('sold_at', [$start, $end]))
            ->sum('total_profit_amount');

        $purchasesTotal = (clone $purchasesInRange)->sum('total_amount');

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
        $receivedPaymentsTotal = (clone $receivablePaymentsInRange)->sum('amount');

        $cashClosures = $cashSessionsInRange->where('status', 'closed')->latest('closed_at')->get();

        $marginByProduct = SaleItem::query()
            ->selectRaw('variant_id, SUM(subtotal_amount) as total_sales_amount, SUM(total_cost_amount) as total_cost_amount, SUM(total_profit_amount) as total_profit_amount')
            ->whereHas('sale', fn ($query) => $query->whereBetween('sold_at', [$start, $end]))
            ->with('variant.product')
            ->groupBy('variant_id')
            ->orderByDesc('total_profit_amount')
            ->get();

        $purchasesBySupplier = Purchase::query()
            ->selectRaw('supplier_id, COUNT(*) as purchases_count, SUM(total_amount) as total_amount')
            ->with('supplier')
            ->whereBetween('purchased_at', [$start, $end])
            ->groupBy('supplier_id')
            ->orderByDesc('total_amount')
            ->get();

        $lotMovements = InventoryLot::query()
            ->with(['variant.product', 'movements' => fn ($query) => $query->latest('movement_at')])
            ->latest('received_at')
            ->limit(50)
            ->get();

        $cashSummary = CashMovement::query()
            ->selectRaw('payment_method, movement_type, SUM(amount) as total_amount')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('payment_method', 'movement_type')
            ->get();

        return compact(
            'start',
            'end',
            'salesTodayTotal',
            'salesTodayPaid',
            'salesTodayCredit',
            'profitToday',
            'purchasesTotal',
            'stockCurrent',
            'lowStock',
            'receivablesOpen',
            'receivablesPendingTotal',
            'receivedPaymentsTotal',
            'cashClosures',
            'marginByProduct',
            'purchasesBySupplier',
            'lotMovements',
            'cashSummary',
        );
    }
}
