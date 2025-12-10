<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Telas - {{ date('d/m/Y') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4CAF50;
        }

        .header h1 {
            color: #4CAF50;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 12px;
        }

        .stats {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .stat-item {
            display: table-cell;
            width: 33.33%;
            padding: 10px;
            text-align: center;
            background: #f5f5f5;
            border: 1px solid #ddd;
        }

        .stat-item .label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }

        .stat-item .value {
            font-size: 18px;
            font-weight: bold;
            color: #4CAF50;
            margin-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
            padding: 8px 6px;
            text-align: left;
            font-size: 10px;
            border: 1px solid #45a049;
        }

        td {
            padding: 6px;
            border: 1px solid #ddd;
            font-size: 10px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #4CAF50;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .totals {
            background-color: #e8f5e9;
            font-weight: bold;
        }

        .totals td {
            border-top: 2px solid #4CAF50;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Inventario de Telas</h1>
        <p>Laboratorio de Costura - Generado el {{ date('d/m/Y H:i') }}</p>
    </div>

    <div class="stats">
        <div class="stat-item">
            <div class="label">Total de Telas</div>
            <div class="value">{{ $telas->count() }}</div>
        </div>
        <div class="stat-item">
            <div class="label">Stock Total</div>
            <div class="value">{{ number_format($totalStock, 2) }} m</div>
        </div>
        <div class="stat-item">
            <div class="label">Valor Total</div>
            <div class="value">${{ number_format($totalValor, 2) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Código</th>
                <th style="width: 20%;">Nombre</th>
                <th style="width: 12%;">Tipo</th>
                <th style="width: 10%;">Color</th>
                <th class="text-center" style="width: 8%;">Ancho (m)</th>
                <th class="text-right" style="width: 10%;">Precio/m</th>
                <th class="text-right" style="width: 10%;">Stock (m)</th>
                <th class="text-right" style="width: 10%;">Valor</th>
                <th style="width: 12%;">Proveedor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($telas as $tela)
            <tr>
                <td>{{ $tela->codigo }}</td>
                <td>{{ $tela->nombre }}</td>
                <td>{{ $tela->tipo }}</td>
                <td>{{ $tela->color }}</td>
                <td class="text-center">{{ number_format($tela->ancho_metros, 2) }}</td>
                <td class="text-right">${{ number_format($tela->precio_metro, 2) }}</td>
                <td class="text-right">{{ number_format($tela->stock_metros, 2) }}</td>
                <td class="text-right">${{ number_format($tela->precio_metro * $tela->stock_metros, 2) }}</td>
                <td>{{ $tela->proveedor }}</td>
            </tr>
            @endforeach

            <tr class="totals">
                <td colspan="6" class="text-right"><strong>TOTALES:</strong></td>
                <td class="text-right"><strong>{{ number_format($totalStock, 2) }} m</strong></td>
                <td class="text-right"><strong>${{ number_format($totalValor, 2) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Documento generado automáticamente por el Sistema de Gestión de Inventario</p>
        <p>© {{ date('Y') }} Laboratorio de Costura - Todos los derechos reservados</p>
    </div>
</body>

</html>