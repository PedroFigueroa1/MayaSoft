<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Flujo de Efectivo - {{ $periodo->nombre }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .company-info {
            text-align: center;
            margin-bottom: 15px;
        }
        .report-info {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .operativo {
            background-color: #e6ffe6;
        }
        .inversion {
            background-color: #e6f7ff;
        }
        .financiacion {
            background-color: #f0e6ff;
        }
        .resumen {
            background-color: #f9f9f9;
        }
        .positive {
            color: #006400;
            font-weight: bold;
        }
        .negative {
            color: #8b0000;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ESTADO DE FLUJO DE EFECTIVO</h1>
    </div>

    <div class="company-info">
        <h2>SISTEMA CONTABLE</h2>
    </div>

    <div class="report-info">
        <table>
            <tr>
                <td width="20%"><strong>Período:</strong></td>
                <td width="30%">{{ $periodo->nombre }}</td>
                <td width="20%"><strong>Fecha de Generación:</strong></td>
                <td width="30%">{{ $fechaGeneracion }}</td>
            </tr>
            <tr>
                <td><strong>Fecha Inicio:</strong></td>
                <td>{{ date('d/m/Y', strtotime($fechaInicio)) }}</td>
                <td><strong>Fecha Fin:</strong></td>
                <td>{{ date('d/m/Y', strtotime($fechaFin)) }}</td>
            </tr>
            <tr>
                <td><strong>Cuenta de Efectivo:</strong></td>
                <td colspan="3">
                    {{ $cuentaEfectivo ? $cuentaEfectivo->codigo . ' - ' . $cuentaEfectivo->nombre : 'Todas las cuentas de efectivo' }}
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th width="70%">DESCRIPCIÓN</th>
                <th width="30%" class="text-right">FLUJO DE EFECTIVO</th>
            </tr>
        </thead>
        <tbody class="operativo">
            <tr>
                <td colspan="2"><strong>ACTIVIDADES OPERATIVAS</strong></td>
            </tr>
            @foreach($actividadesOperativas as $item)
            <tr>
                <td>{{ $item['codigo'] }} - {{ $item['nombre'] }}</td>
                <td class="text-right {{ $item['flujo_neto'] >= 0 ? 'positive' : 'negative' }}">
                    {{ number_format($item['flujo_neto'], 2) }}
                </td>
            </tr>
            @endforeach
            <tr>
                <td><strong>FLUJO NETO DE ACTIVIDADES OPERATIVAS</strong></td>
                <td class="text-right {{ $totalOperativo >= 0 ? 'positive' : 'negative' }}">
                    <strong>{{ number_format($totalOperativo, 2) }}</strong>
                </td>
            </tr>
        </tbody>
        <tbody class="inversion">
            <tr>
                <td colspan="2"><strong>ACTIVIDADES DE INVERSIÓN</strong></td>
            </tr>
            @foreach($actividadesInversion as $item)
            <tr>
                <td>{{ $item['codigo'] }} - {{ $item['nombre'] }}</td>
                <td class="text-right {{ $item['flujo_neto'] >= 0 ? 'positive' : 'negative' }}">
                    {{ number_format($item['flujo_neto'], 2) }}
                </td>
            </tr>
            @endforeach
            <tr>
                <td><strong>FLUJO NETO DE ACTIVIDADES DE INVERSIÓN</strong></td>
                <td class="text-right {{ $totalInversion >= 0 ? 'positive' : 'negative' }}">
                    <strong>{{ number_format($totalInversion, 2) }}</strong>
                </td>
            </tr>
        </tbody>
        <tbody class="financiacion">
            <tr>
                <td colspan="2"><strong>ACTIVIDADES DE FINANCIACIÓN</strong></td>
            </tr>
            @foreach($actividadesFinanciacion as $item)
            <tr>
                <td>{{ $item['codigo'] }} - {{ $item['nombre'] }}</td>
                <td class="text-right {{ $item['flujo_neto'] >= 0 ? 'positive' : 'negative' }}">
                    {{ number_format($item['flujo_neto'], 2) }}
                </td>
            </tr>
            @endforeach
            <tr>
                <td><strong>FLUJO NETO DE ACTIVIDADES DE FINANCIACIÓN</strong></td>
                <td class="text-right {{ $totalFinanciacion >= 0 ? 'positive' : 'negative' }}">
                    <strong>{{ number_format($totalFinanciacion, 2) }}</strong>
                </td>
            </tr>
        </tbody>
        <tbody class="resumen">
            <tr>
                <td><strong>FLUJO NETO DEL PERÍODO</strong></td>
                <td class="text-right {{ $flujoNeto >= 0 ? 'positive' : 'negative' }}">
                    <strong>{{ number_format($flujoNeto, 2) }}</strong>
                </td>
            </tr>
            <tr>
                <td><strong>SALDO INICIAL DE EFECTIVO</strong></td>
                <td class="text-right">{{ number_format($saldoInicial, 2) }}</td>
            </tr>
            <tr>
                <td><strong>SALDO FINAL DE EFECTIVO</strong></td>
                <td class="text-right"><strong>{{ number_format($saldoFinal, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Documento generado el {{ $fechaGeneracion }} | Página 1 de 1</p>
    </div>
</body>
</html>
