@php use App\Support\Money; @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte operativo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111827; }
        h1, h2 { margin-bottom: 8px; }
        .meta { margin-bottom: 24px; color: #4b5563; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-bottom: 24px; }
        .card { border: 1px solid #d1d5db; padding: 12px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; font-size: 12px; }
        th { background: #f3f4f6; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 16px;">
        <button onclick="window.print()">Imprimir</button>
    </div>

    <h1>Reporte operativo</h1>
    <p class="meta">Desde {{ $start->format('Y-m-d') }} hasta {{ $end->format('Y-m-d') }}</p>

    <div class="grid">
        <div class="card"><strong>Ventas netas</strong><br>{{ Money::format($salesNetTotal) }}<br><small>Brutas {{ Money::format($salesGrossTotal) }} · anuladas {{ Money::format($salesVoidedTotal) }}</small></div>
        <div class="card"><strong>Utilidad confiable</strong><br>{{ Money::format($profitReliableTotal) }}<br><small>Warnings {{ Money::format($profitWarningTotal) }} · anulada {{ Money::format($profitVoidedTotal) }}</small></div>
        <div class="card"><strong>Compras netas</strong><br>{{ Money::format($purchasesNetTotal) }}<br><small>Brutas {{ Money::format($purchasesGrossTotal) }} · anuladas {{ Money::format($purchasesVoidedTotal) }}</small></div>
        <div class="card"><strong>Stock total</strong><br>{{ number_format((float) $stockCurrent, 3, '.', '') }}</div>
        <div class="card"><strong>Fiados pendientes</strong><br>{{ Money::format($receivablesPendingTotal) }}</div>
        <div class="card"><strong>Abonos netos</strong><br>{{ Money::format($receivedPaymentsNetTotal) }}<br><small>Brutos {{ Money::format($receivedPaymentsGrossTotal) }} · revertidos {{ Money::format($receivedPaymentsReversedTotal) }}</small></div>
    </div>

    <h2>Resumen bruto y neto</h2>
    <table>
        <thead><tr><th>Bloque</th><th>Bruto</th><th>Anulado / revertido</th><th>Neto / confiable</th></tr></thead>
        <tbody>
            <tr>
                <td>Ventas</td>
                <td>{{ Money::format($salesGrossTotal) }}</td>
                <td>{{ Money::format($salesVoidedTotal) }}</td>
                <td>{{ Money::format($salesNetTotal) }}</td>
            </tr>
            <tr>
                <td>Compras</td>
                <td>{{ Money::format($purchasesGrossTotal) }}</td>
                <td>{{ Money::format($purchasesVoidedTotal) }}</td>
                <td>{{ Money::format($purchasesNetTotal) }}</td>
            </tr>
            <tr>
                <td>Abonos</td>
                <td>{{ Money::format($receivedPaymentsGrossTotal) }}</td>
                <td>{{ Money::format($receivedPaymentsReversedTotal) }}</td>
                <td>{{ Money::format($receivedPaymentsNetTotal) }}</td>
            </tr>
            <tr>
                <td>Utilidad</td>
                <td>—</td>
                <td>Warnings {{ Money::format($profitWarningTotal) }} · anulada {{ Money::format($profitVoidedTotal) }}</td>
                <td>{{ Money::format($profitReliableTotal) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Productos por agotarse</h2>
    <table>
        <thead><tr><th>Producto</th><th>Variante</th><th>Disponible</th></tr></thead>
        <tbody>
            @forelse ($lowStock as $row)
                <tr>
                    <td>{{ $row->variant->product->name }}</td>
                    <td>{{ $row->variant->name }}</td>
                    <td>{{ number_format((float) $row->total_available, 3, '.', '') }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No hay productos críticos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Compras por proveedor</h2>
    <table>
        <thead><tr><th>Proveedor</th><th>Compras</th><th>Total</th></tr></thead>
        <tbody>
            @forelse ($purchasesBySupplier as $row)
                <tr>
                    <td>{{ $row->supplier?->name ?? 'Sin proveedor' }}</td>
                    <td>{{ $row->purchases_count }}</td>
                    <td>{{ Money::format((int) $row->total_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No hay compras en el período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Margen por producto</h2>
    <table>
        <thead><tr><th>Producto</th><th>Ventas</th><th>Costo</th><th>Utilidad</th></tr></thead>
        <tbody>
            @forelse ($marginByProduct as $row)
                <tr>
                    <td>{{ $row->variant->product->name }} — {{ $row->variant->name }}</td>
                    <td>{{ Money::format((int) $row->total_sales_amount) }}</td>
                    <td>{{ Money::format((int) $row->total_cost_amount) }}</td>
                    <td>{{ Money::format((int) $row->total_profit_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No hay ventas en el período.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Resumen de caja</h2>
    <table>
        <thead><tr><th>Concepto</th><th>Total</th></tr></thead>
        <tbody>
            <tr><td>Operativa</td><td>{{ Money::format($cashOperationalTotal) }}</td></tr>
            <tr><td>Reversas</td><td>{{ Money::format($cashReversalTotal) }}</td></tr>
            <tr><td>Manual / otros</td><td>{{ Money::format($cashManualTotal) }}</td></tr>
            <tr><td>Neto final</td><td>{{ Money::format($cashNetTotal) }}</td></tr>
        </tbody>
    </table>
</body>
</html>
