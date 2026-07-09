<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sales\StorePosSaleRequest;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\InventoryLot;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\SalePresentation;
use App\Models\UserSetting;
use App\Support\Sales\CreateSaleService;
use App\Support\Sales\PosSaleDraftBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        $presentations = SalePresentation::query()
            ->with(['variant.product', 'variant.nearestLot', 'prices' => fn ($q) => $q->orderByDesc('starts_at')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $availableBaseUnitsByVariant = InventoryLot::query()
            ->selectRaw('variant_id, COALESCE(SUM(available_quantity), 0) as available_quantity')
            ->whereIn('variant_id', $presentations->pluck('product_variant_id')->unique()->all())
            ->groupBy('variant_id')
            ->pluck('available_quantity', 'variant_id');

        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        $clientes = $this->buildClientesList($customers);
        $defaultClienteId = Customer::default()->value('id');

        return view('pos.index', [
            'customers' => $customers,
            'clientes' => $clientes,
            'defaultClienteId' => $defaultClienteId,
            'presentations' => $presentations,
            'availableBaseUnitsByVariant' => $availableBaseUnitsByVariant,
            'currentCashSession' => CashSession::query()->where('status', 'open')->latest('opened_at')->first(),
            'fiadoAutoEnabled' => UserSetting::get(auth()->id(), 'fiado_auto_enabled', 'true') === 'true',
        ]);
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $query = trim($request->input('q', ''));

        if ($query === '') {
            return response()->json(['results' => []]);
        }

        $customers = Customer::query()
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', '%'.$query.'%')
                    ->orWhere('phone', 'LIKE', '%'.$query.'%')
                    ->orWhere('document', 'LIKE', '%'.$query.'%');
            })
            ->limit(10)
            ->get();

        $openDebtByCustomer = Receivable::query()
            ->selectRaw('customer_id, COALESCE(SUM(pending_amount), 0) as pending')
            ->where('status', 'open')
            ->whereIn('customer_id', $customers->pluck('id')->all())
            ->groupBy('customer_id')
            ->pluck('pending', 'customer_id');

        return response()->json([
            'results' => $customers->map(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'document' => $c->document,
                'phone' => $c->phone,
                'is_default' => (bool) $c->is_default,
                'saldo_fiado' => round(((int) ($openDebtByCustomer[$c->id] ?? 0)) / 100, 2),
            ])->values(),
        ]);
    }

    public function store(
        StorePosSaleRequest $request,
        PosSaleDraftBuilder $draftBuilder,
        CreateSaleService $createSaleService,
    ): RedirectResponse {
        $validated = $request->validated();
        $draft = $draftBuilder->build($validated);

        if (($validated['action'] ?? 'checkout') === 'complete') {
            return redirect()->route('sales.create')
                ->withInput($draftBuilder->toFullSaleInput($draft, $validated))
                ->with('status', 'Continuando en venta completa desde POS.');
        }

        if ($draft['requires_full_sale']) {
            return redirect()->route('sales.create')
                ->withInput($draftBuilder->toFullSaleInput($draft, $validated))
                ->with('status', $draft['full_sale_reason'] ?? 'Este caso requiere venta completa para no romper trazabilidad.');
        }

        $currentCashSession = CashSession::query()->where('status', 'open')->latest('opened_at')->first();

        if ($currentCashSession === null) {
            return back()
                ->withErrors(['pos' => 'Debes abrir una caja antes de cobrar desde POS.'])
                ->withInput();
        }

        $sale = $createSaleService->handle(
            $draftBuilder->toCreateSalePayload($draft, $validated),
            $request->user()->id,
            $currentCashSession,
        );

        return redirect()->route('pos.index')
            ->with('status', "Venta #{$sale->id} registrada correctamente desde POS.")
            ->with('receipt_sale_id', $sale->id);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Customer>  $customers
     * @return array<int, array{id:int,name:string,document:?string,saldo_fiado:float,is_default:bool}>
     */
    private function buildClientesList($customers): array
    {
        if ($customers->isEmpty()) {
            return [];
        }

        $openDebtByCustomer = DB::table('receivables')
            ->selectRaw('customer_id, COALESCE(SUM(pending_amount), 0) as pending')
            ->where('status', 'open')
            ->whereIn('customer_id', $customers->pluck('id')->all())
            ->groupBy('customer_id')
            ->pluck('pending', 'customer_id');

        return $customers->map(fn (Customer $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'document' => $c->document,
            'saldo_fiado' => round(((int) ($openDebtByCustomer[$c->id] ?? 0)) / 100, 2),
            'is_default' => (bool) $c->is_default,
        ])->values()->all();
    }
}
