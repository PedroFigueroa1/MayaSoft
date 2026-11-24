<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estado de Resultados - {{ $periodo->nombre }}</title>
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
        .ingresos {
            background-color: #e6ffe6;
        }
        .gastos {
            background-color: #ffe6e6;
        }
        .resultado {
            background-color: #f0f0f0;
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
        <h1>ESTADO DE RESULTADOS</h1>
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
                <td><strong>Duración:</strong></td>
                <td>{{ $dias = (strtotime($fechaFin) - strtotime($fechaInicio)) / (60 * 60 * 24) + 1 }} días</td>
                <td><strong>Estado:</strong></td>
                <td>{{ $periodo->estado == 'A' ? 'Abierto' : 'Cerrado' }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th width="70%">DESCRIPCIÓN</th>
                <th width="30%" class="text-right">MONTO</th>
            </tr>
        </thead>
        <tbody class="ingresos">
            <tr>
                <td colspan="2"><strong>INGRESOS</strong></td>
            </tr>
            @foreach($ingresos as $cuenta)
            <tr>
                <td>{{ $cuenta['codigo'] }} - {{ $cuenta['nombre'] }}</td>
                <td class="text-right">{{ number_format($cuenta['saldo'], 2) }}</td>
            </tr>
            @endforeach
            <tr>
                <td><strong>TOTAL INGRESOS</strong></td>
                <td class="text-right"><strong>{{ number_format($totalIngresos, 2) }}</strong></td>
            </tr>
        </tbody>
        <tbody class="gastos">
            <tr>
                <td colspan="2"><strong>GASTOS</strong></td>
            </tr>
            @foreach($gastos as $cuenta)
            <tr>
                <td>{{ $cuenta['codigo'] }} - {{ $cuenta['nombre'] }}</td>
                <td class="text-right">{{ number_format($cuenta['saldo'], 2) }}</td>
            </tr>
            @endforeach
            <tr>
                <td><strong>TOTAL GASTOS</strong></td>
                <td class="text-right"><strong>{{ number_format($totalGastos, 2) }}</strong></td>
            </tr>
        </tbody>
        <tbody class="resultado">
            <tr>
                <td><strong>RESULTADO DEL EJERCICIO</strong></td>
                <td class="text-right {{ $resultadoEjercicio >= 0 ? 'positive' : 'negative' }}">
                    <strong>{{ number_format($resultadoEjercicio, 2) }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <table>
            <tr>
                <td width="25%"><strong>Total Ingresos:</strong></td>
                <td width="25%" class="text-right">{{ number_format($totalIngresos, 2) }}</td>
                <td width="25%"><strong>Total Gastos:</strong></td>
                <td width="25%" class="text-right">{{ number_format($totalGastos, 2) }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>Resultado:</strong></td>
                <td colspan="2" class="text-center {{ $resultadoEjercicio >= 0 ? 'positive' : 'negative' }}">
                    <strong>
                        {{ $resultadoEjercicio > 0 ? 'UTILIDAD NETA' : ($resultadoEjercicio < 0 ? 'PÉRDIDA NETA' : 'PUNTO DE EQUILIBRIO') }}:
                        {{ number_format(abs($resultadoEjercicio), 2) }}
                    </strong>
                </td>
            </tr>
            @if($totalIngresos > 0)
            <tr>
                <td colspan="4" class="text-center">
                    Margen de {{ $resultadoEjercicio !== 0 ? (abs($resultadoEjercicio) / $totalIngresos * 100) : 0 }}%
                    {{ $resultadoEjercicio > 0 ? 'de utilidad' : 'de pérdida' }} sobre los ingresos
                </td>
            </tr>
            @endif
        </table>
    </div>

    <div class="footer">
        <p>Documento generado el {{ $fechaGeneracion }} | Página 1 de 1</p>
    </div>
</body>
</html>
