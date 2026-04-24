@php use App\Support\Money; @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ventas PDF</title>
    @include('reports.pdf.partials.styles')
</head>
<body>
    <h1>Reporte de ventas</h1>
    <p class="meta">Desde {{ $range->start->format('Y-m-d') }} hasta {{ $range->end->format('Y-m-d') }}</p>

    <div class="cards">
        <div class="card"><p class="label">Ventas</p><p class="value">{{ $sales->count() }}</p></div>
        <div class="card"><p class="label">Cobrado</p><p class="value">{{ Money::format((int) $sales->sum('paid_amount')) }}</p></div>
        <div class="card"><p class="label">Fiado</p><p class="value">{{ Money::format((int) $sales->sum('credit_amount')) }}</p></div>
    </div>

    <div class="section-title">Resumen</div>
    <table>
        <thead>
            <tr><th>ID</th><th>Fecha</th><th>Cliente</th><th>Usuario</th><th>Estado</th><th>Total</th><th>Pagado</th><th>Fiado</th><th>Notas</th></tr>
        </thead>
        <tbody>
            @forelse ($sales as $sale)
                <tr>
                    <td>{{ $sale->id }}</td>
                    <td>{{ optional($sale->sold_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $sale->customer?->name ?? 'Venta anónima' }}</td>
                    <td>{{ $sale->creator?->name ?? '—' }}</td>
                    <td>{{ $sale->isVoided() ? 'Anulada' : ($sale->credit_amount > 0 ? 'Con saldo pendiente' : 'Cobrada') }}</td>
                    <td>{{ Money::format($sale->total_amount) }}</td>
                    <td>{{ Money::format($sale->paid_amount) }}</td>
                    <td>{{ Money::format($sale->credit_amount) }}</td>
                    <td>{{ $sale->notes }}</td>
                </tr>
            @empty
                <tr><td colspan="9">No hay ventas en el período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Detalle por línea</div>
    <table>
        <thead>
            <tr><th>Venta</th><th>Producto</th><th>Variante</th><th>Cantidad</th><th>Precio</th><th>Override</th><th>Warnings</th><th>Utilidad</th></tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $item->sale?->id }}</td>
                    <td>{{ $item->variant?->product?->name }}</td>
                    <td>{{ $item->variant?->name }}</td>
                    <td>{{ number_format((float) $item->quantity, 3, '.', '') }}</td>
                    <td>{{ Money::format($item->unit_price_amount) }}</td>
                    <td>{{ $item->has_manual_price_override ? 'Sí' : 'No' }} {{ $item->manual_price_reason ? '· '.$item->manual_price_reason : '' }}</td>
                    <td>{{ $item->has_stock_warning ? 'Stock ' : '' }}{{ $item->has_cost_warning ? 'Costo' : '' }}</td>
                    <td>{{ Money::format((int) $item->total_profit_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No hay líneas en el período.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
