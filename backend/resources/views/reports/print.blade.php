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
        <div class="card"><strong>Ventas</strong><br>{{ Money::format($salesTodayTotal) }}</div>
        <div class="card"><strong>Utilidad</strong><br>{{ Money::format($profitToday) }}</div>
        <div class="card"><strong>Compras</strong><br>{{ Money::format($purchasesTotal) }}</div>
        <div class="card"><strong>Stock total</strong><br>{{ number_format((float) $stockCurrent, 3, '.', '') }}</div>
        <div class="card"><strong>Fiados pendientes</strong><br>{{ Money::format($receivablesPendingTotal) }}</div>
        <div class="card"><strong>Abonos recibidos</strong><br>{{ Money::format($receivedPaymentsTotal) }}</div>
    </div>

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
</body>
</html>
