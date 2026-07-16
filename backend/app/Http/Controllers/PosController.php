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

        return view(
            'pos.index',
            [
                'customers' => $customers,
                'clientes' => $clientes,
                'defaultClienteId' => $defaultClienteId,
                'presentations' => $presentations,
                'availableBaseUnitsByVariant' => $availableBaseUnitsByVariant,
                'currentCashSession' => CashSession::query()->where('status', 'open')->latest('opened_at')->first(),
                'fiadoAutoEnabled' => UserSetting::get(auth()->id(), 'fiado_auto_enabled', 'true') === 'true',
            ]
        );
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

    public function storeCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
        ]);

        $customer = Customer::query()->create([
            ...$validated,
            'is_active' => true,
        ]);

        return response()->json([
            'id' => $customer->id,
            'name' => $customer->name,
            'document' => $customer->document,
            'phone' => $customer->phone,
            'is_default' => false,
            'saldo_fiado' => 0,
        ], 201);
    }

    public function store(
        StorePosSaleRequest $request,
        PosSaleDraftBuilder $draftBuilder,
        CreateSaleService $createSaleService,
    ): JsonResponse {
        // AJAX-only path (PR 3 cutover): no caja abierta check, no redirect,
        // returns JSON. The new view's `metodo` field has already been
        // translated to `payment_method` + `allow_credit_sale` + `confirm_credit_sale`
        // in `StorePosSaleRequest::prepareForValidation()`.
        $validated = $request->validated();
        $draft = $draftBuilder->build($validated);

        if ($draft['requires_full_sale']) {
            return response()->json([
                'ok' => false,
                'message' => $draft['full_sale_reason'] ?? 'Este caso requiere venta completa para no romper trazabilidad.',
            ], 422);
        }

        try {
            $sale = $createSaleService->handle(
                $draftBuilder->toCreateSalePayload($draft, $validated),
                $request->user()->id,
                null,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'No se pudo cobrar la venta.',
                'errors' => $e->errors(),
            ], 422);
        }

        $methodLabel = match ($validated['payment_method'] ?? 'cash') {
            'transfer' => 'transferencia',
            'mixed' => 'mixto',
            default => ($validated['allow_credit_sale'] ?? false) ? 'fiado' : 'efectivo',
        };

        return response()->json([
            'ok' => true,
            'sale_id' => $sale->id,
            'message' => sprintf('Venta cobrada: %s (%s).', number_format($sale->total_amount / 100, 2, '.', ','), $methodLabel),
        ]);
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
            'phone' => $c->phone,
            'saldo_fiado' => round(((int) ($openDebtByCustomer[$c->id] ?? 0)) / 100, 2),
            'is_default' => (bool) $c->is_default,
        ])->values()->all();
    }
}
