@php use App\Support\Money; @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Compras PDF</title>
    @include('reports.pdf.partials.styles')
</head>
<body>
    <h1>Reporte de compras</h1>
    <p class="meta">Desde {{ $range->start->format('Y-m-d') }} hasta {{ $range->end->format('Y-m-d') }}</p>

    <div class="cards">
        <div class="card"><p class="label">Compras</p><p class="value">{{ $purchases->count() }}</p></div>
        <div class="card"><p class="label">Confirmadas</p><p class="value">{{ $purchases->where('status', 'confirmed')->count() }}</p></div>
        <div class="card"><p class="label">Total neto</p><p class="value">{{ Money::format((int) $purchases->where('status', 'confirmed')->sum('total_amount')) }}</p></div>
    </div>

    <div class="section-title">Resumen</div>
    <table>
        <thead>
            <tr><th>ID</th><th>Fecha</th><th>Proveedor</th><th>Modo</th><th>Pago</th><th>Factura</th><th>Total</th><th>Notas</th></tr>
        </thead>
        <tbody>
            @forelse ($purchases as $purchase)
                <tr>
                    <td>{{ $purchase->id }}</td>
                    <td>{{ optional($purchase->purchased_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $purchase->supplier?->name ?? '—' }}</td>
                    <td>{{ $purchase->isDetailed() ? 'Detallada' : 'Rápida' }}</td>
                    <td>{{ $purchase->payment_type }}</td>
                    <td>{{ $purchase->invoice_number }}</td>
                    <td>{{ Money::format($purchase->total_amount) }}</td>
                    <td>{{ $purchase->notes }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No hay compras en el período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Detalle por línea</div>
    <table>
        <thead>
            <tr><th>Compra</th><th>Producto</th><th>Variante</th><th>Tipo</th><th>Cantidad</th><th>Bonificación</th><th>Costo final</th><th>Notas</th></tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $item->purchase?->id }}</td>
                    <td>{{ $item->variant?->product?->name }}</td>
                    <td>{{ $item->variant?->name }}</td>
                    <td>{{ $item->isBonusLine() ? 'Bonificación' : 'Normal' }}</td>
                    <td>{{ number_format((float) $item->quantity, 3, '.', '') }}</td>
                    <td>{{ number_format((float) $item->bonus_quantity, 3, '.', '') }}</td>
                    <td>{{ Money::format((int) $item->total_cost_amount) }}</td>
                    <td>{{ $item->notes }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No hay líneas en el período.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
