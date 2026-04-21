<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReceivableController extends Controller
{
    public function index(): View
    {
        return view('receivables.index', [
            'receivables' => Receivable::query()->with(['customer', 'sale', 'payments'])->latest('opened_at')->get(),
        ]);
    }

    public function show(Receivable $receivable): View
    {
        $receivable->load(['customer', 'sale.creator', 'payments.creator']);

        return view('receivables.show', [
            'receivable' => $receivable,
            'currentCashSession' => CashSession::query()->where('status', 'open')->latest('opened_at')->first(),
        ]);
    }

    public function storePayment(Request $request, Receivable $receivable): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'max:50'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $receivable, $request) {
            $amount = Money::dollarsToCents($validated['amount']);
            abort_if($amount > $receivable->pending_amount, 422, 'El abono no puede exceder el saldo pendiente.');
            $currentCashSession = CashSession::query()->where('status', 'open')->latest('opened_at')->first();

            if ($currentCashSession === null) {
                throw ValidationException::withMessages([
                    'amount' => 'Debes abrir una caja antes de registrar abonos.',
                ]);
            }

            $payment = ReceivablePayment::query()->create([
                'receivable_id' => $receivable->id,
                'cash_session_id' => $currentCashSession->id,
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'paid_at' => $validated['paid_at'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $newPending = $receivable->pending_amount - $amount;

            $receivable->update([
                'pending_amount' => $newPending,
                'status' => $newPending === 0 ? 'paid' : 'open',
            ]);

            if ($payment->cash_session_id !== null) {
                CashMovement::query()->create([
                    'cash_session_id' => $payment->cash_session_id,
                    'movement_type' => 'receivable_payment',
                    'amount' => $amount,
                    'payment_method' => $validated['payment_method'],
                    'reference_type' => 'receivable',
                    'reference_id' => $receivable->id,
                    'notes' => 'Abono de cuenta por cobrar',
                    'created_by' => $request->user()->id,
                    'created_at' => $validated['paid_at'],
                ]);
            }
        });

        return redirect()->route('receivables.show', $receivable)->with('status', 'Abono registrado correctamente.');
    }
}
