<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Inspección</title>
    <!-- Tailwind CSS for PDF - Spatie PDF supports this if Browsershot is configured correctly -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: sans-serif;
            font-size: 0.8rem;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body class="p-8">

    <!-- Header -->
    <div class="flex justify-between items-center mb-8 border-b pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Reporte de Inspección</h1>
            <p class="text-sm text-gray-500">Rango: {{ \Carbon\Carbon::parse($fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fecha_fin)->format('d/m/Y') }}</p>
        </div>
        <div class="text-right">
            <p class="font-bold">Laboratorio de Calidad</p>
            <p class="text-xs text-gray-500">Generado: {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="bg-gray-50 border p-4 rounded text-center">
            <h3 class="text-gray-500 text-xs uppercase font-bold">Total Rollos</h3>
            <p class="text-2xl font-bold text-blue-600">{{ $kpis['total_rollos'] }}</p>
        </div>
        <div class="bg-gray-50 border p-4 rounded text-center">
            <h3 class="text-gray-500 text-xs uppercase font-bold">Metros Totales</h3>
            <p class="text-2xl font-bold text-green-600">{{ number_format($kpis['total_metros'], 2) }}</p>
        </div>
        <div class="bg-gray-50 border p-4 rounded text-center">
            <h3 class="text-gray-500 text-xs uppercase font-bold">Porcentaje Rechazo</h3>
            <p class="text-2xl font-bold text-red-600">{{ number_format($kpis['tasa_rechazo'], 1) }}%</p>
        </div>
    </div>

    <!-- Table -->
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-100 border-b border-gray-300">
                <th class="p-2 font-bold text-gray-700">Fecha</th>
                <th class="p-2 font-bold text-gray-700">Inspector</th>
                <th class="p-2 font-bold text-gray-700">Prov / Art</th>
                <th class="p-2 font-bold text-gray-700">Lote Int.</th>
                <th class="p-2 font-bold text-gray-700">Lote Teñido</th>
                <th class="p-2 font-bold text-gray-700 text-right">Puntos Totals</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportes as $reporte)
            <tr class="border-b border-gray-100 text-gray-600 odd:bg-white even:bg-gray-50">
                <td class="p-2">{{ $reporte->created_at->format('d/m/Y') }}</td>
                <td class="p-2">{{ $reporte->auditor->name ?? 'N/A' }}</td>
                <td class="p-2">
                    <div class="font-bold">{{ $reporte->proveedor }}</div>
                    <div class="text-xs">{{ $reporte->articulo }}</div>
                </td>
                <td class="p-2">{{ $reporte->lote_intimark }}</td>
                {{-- Asumiendo que detallamos el primer lote si hay múltiples, o listamos --}}
                <td class="p-2 text-xs">
                    @foreach($reporte->detalles as $d)
                    {{ $d->numero_lote }}<br>
                    @endforeach
                </td>
                <td class="p-2 text-right font-mono">
                    @foreach($reporte->detalles as $d)
                    {{ $d->puntos_1 + ($d->puntos_2*2) + ($d->puntos_3*3) + ($d->puntos_4*4) }} pts<br>
                    @endforeach
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>