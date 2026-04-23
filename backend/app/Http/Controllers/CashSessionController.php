<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashSessionController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : now()->startOfMonth();
        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : now()->endOfDay();

        $closedSessions = CashSession::query()
            ->with('opener')
            ->where('status', 'closed')
            ->whereBetween('closed_at', [$startDate, $endDate])
            ->latest('closed_at')
            ->get();

        $balancedSessions = $closedSessions->where('difference_amount', 0);
        $shortageSessions = $closedSessions->filter(fn (CashSession $session) => (int) $session->difference_amount < 0);
        $surplusSessions = $closedSessions->filter(fn (CashSession $session) => (int) $session->difference_amount > 0);
        $cashMovementAnalysis = $this->buildMovementAnalysis($closedSessions);

        return view('cash.index', [
            'currentSession' => CashSession::query()->with('opener')->where('status', 'open')->latest('opened_at')->first(),
            'sessions' => CashSession::query()->with('opener')->latest('opened_at')->limit(20)->get(),
            'closedSessions' => $closedSessions,
            'cashSummary' => [
                'closed_sessions_count' => $closedSessions->count(),
                'expected_cash_amount' => (int) $closedSessions->sum('expected_cash_amount'),
                'counted_cash_amount' => (int) $closedSessions->sum('counted_cash_amount'),
                'expected_transfer_amount' => (int) $closedSessions->sum('expected_transfer_amount'),
                'difference_amount' => (int) $closedSessions->sum('difference_amount'),
                'balanced_sessions_count' => $balancedSessions->count(),
                'shortage_sessions_count' => $shortageSessions->count(),
                'surplus_sessions_count' => $surplusSessions->count(),
                'shortage_total_amount' => abs((int) $shortageSessions->sum('difference_amount')),
                'surplus_total_amount' => (int) $surplusSessions->sum('difference_amount'),
                'largest_shortage_amount' => abs((int) ($shortageSessions->min('difference_amount') ?? 0)),
                'largest_surplus_amount' => (int) ($surplusSessions->max('difference_amount') ?? 0),
            ],
            'cashRange' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'cashMovementAnalysis' => $cashMovementAnalysis,
        ]);
    }

    public function create(): View
    {
        abort_if(CashSession::query()->where('status', 'open')->exists(), 422, 'Ya existe una caja abierta.');

        return view('cash.open');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(CashSession::query()->where('status', 'open')->exists(), 422, 'Ya existe una caja abierta.');

        $validated = $request->validate([
            'opened_at' => ['required', 'date'],
            'opening_amount' => ['required', 'numeric', 'gte:0'],
        ]);

        $session = CashSession::query()->create([
            'opened_by' => $request->user()->id,
            'opened_at' => $validated['opened_at'],
            'opening_amount' => Money::dollarsToCents($validated['opening_amount']),
            'status' => 'open',
        ]);

        CashMovement::query()->create([
            'cash_session_id' => $session->id,
            'movement_type' => 'opening',
            'amount' => $session->opening_amount,
            'payment_method' => 'cash',
            'reference_type' => 'cash_session',
            'reference_id' => $session->id,
            'notes' => 'Apertura de caja',
            'created_by' => $request->user()->id,
            'created_at' => $validated['opened_at'],
        ]);

        return redirect()->route('cash.index')->with('status', 'Caja abierta correctamente.');
    }

    public function closeForm(CashSession $cash): View
    {
        abort_if($cash->status !== 'open', 422, 'La caja seleccionada ya está cerrada.');

        [$expectedCash, $expectedTransfer] = $this->calculateExpectedTotals($cash);

        return view('cash.close', [
            'cashSession' => $cash,
            'expectedCash' => $expectedCash,
            'expectedTransfer' => $expectedTransfer,
        ]);
    }

    public function close(Request $request, CashSession $cash): RedirectResponse
    {
        abort_if($cash->status !== 'open', 422, 'La caja seleccionada ya está cerrada.');

        $validated = $request->validate([
            'counted_cash_amount' => ['required', 'numeric', 'gte:0'],
            'closing_notes' => ['nullable', 'string'],
            'closed_at' => ['required', 'date'],
        ]);

        [$expectedCash, $expectedTransfer] = $this->calculateExpectedTotals($cash);
        $countedCash = Money::dollarsToCents($validated['counted_cash_amount']);

        $cash->update([
            'status' => 'closed',
            'closed_at' => $validated['closed_at'],
            'expected_cash_amount' => $expectedCash,
            'counted_cash_amount' => $countedCash,
            'expected_transfer_amount' => $expectedTransfer,
            'difference_amount' => $countedCash - $expectedCash,
            'closing_notes' => $validated['closing_notes'] ?? null,
        ]);

        return redirect()->route('cash.index')->with('status', 'Caja cerrada correctamente.');
    }

    public function storeMovement(Request $request, CashSession $cash): RedirectResponse
    {
        abort_if($cash->status !== 'open', 422, 'Solo se pueden registrar movimientos en una caja abierta.');

        $validated = $request->validate([
            'movement_type' => ['required', Rule::in(['expense', 'withdrawal', 'manual_income'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['nullable', Rule::in(['cash', 'transfer'])],
            'notes' => ['nullable', 'string'],
        ]);

        [$expectedCash, $expectedTransfer] = $this->calculateExpectedTotals($cash);
        $amount = Money::dollarsToCents($validated['amount']);
        $paymentMethod = $validated['payment_method'] ?? 'cash';

        if (in_array($validated['movement_type'], ['expense', 'withdrawal'], true)) {
            $available = $paymentMethod === 'transfer' ? $expectedTransfer : $expectedCash;

            if ($amount > $available) {
                throw ValidationException::withMessages([
                    'amount' => 'El movimiento excede el saldo esperado disponible en caja para ese método.',
                ]);
            }
        }

        CashMovement::query()->create([
            'cash_session_id' => $cash->id,
            'movement_type' => $validated['movement_type'],
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'reference_type' => 'manual_cash_movement',
            'reference_id' => null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        return redirect()->route('cash.index')->with('status', 'Movimiento de caja registrado.');
    }

    private function calculateExpectedTotals(CashSession $cash): array
    {
        $movements = $cash->movements;

        $expectedCash = (int) $movements
            ->where('payment_method', 'cash')
            ->sum(function (CashMovement $movement) {
                return $this->signedAmount($movement);
            });

        $expectedTransfer = (int) $movements
            ->where('payment_method', 'transfer')
            ->sum(function (CashMovement $movement) {
                return $this->signedAmount($movement);
            });

        return [$expectedCash, $expectedTransfer];
    }

    private function signedAmount(CashMovement $movement): int
    {
        return match ($movement->movement_type) {
            'expense', 'withdrawal' => -1 * $movement->amount,
            default => $movement->amount,
        };
    }

    private function buildMovementAnalysis($closedSessions): array
    {
        $movements = CashMovement::query()
            ->whereIn('cash_session_id', $closedSessions->pluck('id'))
            ->orderBy('created_at')
            ->get();

        $rows = [];

        foreach ($movements as $movement) {
            $type = $movement->movement_type;
            $method = $movement->payment_method ?? 'unassigned';
            $signedAmount = $this->signedAmount($movement);

            if (! isset($rows[$type])) {
                $rows[$type] = [
                    'label' => $this->movementTypeLabel($type),
                    'cash_amount' => 0,
                    'transfer_amount' => 0,
                    'other_amount' => 0,
                    'total_amount' => 0,
                ];
            }

            match ($method) {
                'cash' => $rows[$type]['cash_amount'] += $signedAmount,
                'transfer' => $rows[$type]['transfer_amount'] += $signedAmount,
                default => $rows[$type]['other_amount'] += $signedAmount,
            };

            $rows[$type]['total_amount'] += $signedAmount;
        }

        return [
            'rows' => array_values($rows),
            'totals' => [
                'cash_amount' => array_sum(array_column($rows, 'cash_amount')),
                'transfer_amount' => array_sum(array_column($rows, 'transfer_amount')),
                'other_amount' => array_sum(array_column($rows, 'other_amount')),
                'total_amount' => array_sum(array_column($rows, 'total_amount')),
            ],
        ];
    }

    private function movementTypeLabel(string $type): string
    {
        return match ($type) {
            'opening' => 'Apertura',
            'sale_payment' => 'Pago de venta',
            'sale_payment_reversal' => 'Reversa de pago de venta',
            'receivable_payment' => 'Abono de cuenta por cobrar',
            'receivable_payment_reversal' => 'Reversa de abono',
            'manual_income' => 'Ingreso extraordinario',
            'expense' => 'Gasto',
            'withdrawal' => 'Retiro',
            default => str($type)->replace('_', ' ')->title()->toString(),
        };
    }
}
