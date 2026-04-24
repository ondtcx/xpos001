@php use App\Support\Money; @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cobranza PDF</title>
    @include('reports.pdf.partials.styles')
</head>
<body>
    <h1>Reporte de cobranza</h1>
    <p class="meta">Desde {{ $range->start->format('Y-m-d') }} hasta {{ $range->end->format('Y-m-d') }}</p>

    <div class="cards">
        <div class="card"><p class="label">Cuentas</p><p class="value">{{ $receivables->count() }}</p></div>
        <div class="card"><p class="label">Pendiente</p><p class="value">{{ Money::format((int) $receivables->sum('pending_amount')) }}</p></div>
        <div class="card"><p class="label">Abonos</p><p class="value">{{ Money::format((int) $payments->where('is_reversed', false)->sum('amount')) }}</p></div>
    </div>

    <div class="section-title">Resumen de cuentas</div>
    <table>
        <thead>
            <tr><th>Cuenta</th><th>Fecha</th><th>Cliente</th><th>Venta</th><th>Original</th><th>Pendiente</th><th>Estado</th></tr>
        </thead>
        <tbody>
            @forelse ($receivables as $receivable)
                <tr>
                    <td>{{ $receivable->id }}</td>
                    <td>{{ optional($receivable->opened_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $receivable->customer?->name ?? '—' }}</td>
                    <td>{{ $receivable->sale?->id }}</td>
                    <td>{{ Money::format($receivable->original_amount) }}</td>
                    <td>{{ Money::format($receivable->pending_amount) }}</td>
                    <td>{{ $receivable->status }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No hay cuentas en el período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Detalle de abonos</div>
    <table>
        <thead>
            <tr><th>Abono</th><th>Cuenta</th><th>Fecha</th><th>Cliente</th><th>Método</th><th>Monto</th><th>Revertido</th><th>Motivo</th></tr>
        </thead>
        <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td>{{ $payment->id }}</td>
                    <td>{{ $payment->receivable_id }}</td>
                    <td>{{ optional($payment->paid_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $payment->receivable?->customer?->name ?? '—' }}</td>
                    <td>{{ $payment->payment_method }}</td>
                    <td>{{ Money::format($payment->amount) }}</td>
                    <td>{{ $payment->is_reversed ? 'Sí' : 'No' }}</td>
                    <td>{{ $payment->reversal_reason }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No hay abonos en el período.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
