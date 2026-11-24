<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance General - {{ $periodo->nombre }}</title>
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
        .summary {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        .activos {
            background-color: #e6f7ff;
        }
        .pasivos {
            background-color: #e6ffe6;
        }
        .patrimonio {
            background-color: #fff0e6;
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
        <h1>BALANCE GENERAL</h1>
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
                <td><strong>Fecha de Corte:</strong></td>
                <td>{{ date('d/m/Y', strtotime($fechaCorte)) }}</td>
                <td><strong>Estado:</strong></td>
                <td>{{ $periodo->estado == 'A' ? 'Abierto' : 'Cerrado' }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th width="60%">ACTIVOS</th>
                <th width="20%" class="text-right">Debe</th>
                <th width="20%" class="text-right">Haber</th>
            </tr>
        </thead>
        <tbody class="activos">
            @foreach($activos as $cuenta)
            <tr>
                <td>{{ $cuenta['codigo'] }} - {{ $cuenta['nombre'] }}</td>
                <td class="text-right">{{ $cuenta['saldo'] > 0 ? number_format($cuenta['saldo'], 2) : '' }}</td>
                <td class="text-right">{{ $cuenta['saldo'] < 0 ? number_format(abs($cuenta['saldo']), 2) : '' }}</td>
            </tr>
            @endforeach
            <tr>
                <td><strong>TOTAL ACTIVOS</strong></td>
                <td class="text-right"><strong>{{ number_format($totalActivos, 2) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th width="60%">PASIVOS Y PATRIMONIO</th>
                <th width="20%" class="text-right">Debe</th>
                <th width="20%" class="text-right">Haber</th>
            </tr>
        </thead>
        <tbody class="pasivos">
            <tr>
                <td colspan="3"><strong>PASIVOS</strong></td>
            </tr>
            @foreach($pasivos as $cuenta)
            <tr>
                <td>{{ $cuenta['codigo'] }} - {{ $cuenta['nombre'] }}</td>
                <td class="text-right">{{ $cuenta['saldo'] < 0 ? number_format(abs($cuenta['saldo']), 2) : '' }}</td>
                <td class="text-right">{{ $cuenta['saldo'] > 0 ? number_format($cuenta['saldo'], 2) : '' }}</td>
            </tr>
            @endforeach
            <tr>
                <td><strong>TOTAL PASIVOS</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($totalPasivos, 2) }}</strong></td>
            </tr>
        </tbody>
        <tbody class="patrimonio">
            <tr>
                <td colspan="3"><strong>PATRIMONIO</strong></td>
            </tr>
            @foreach($patrimonio as $cuenta)
            <tr>
                <td>{{ $cuenta['codigo'] }} - {{ $cuenta['nombre'] }}</td>
                <td class="text-right">{{ $cuenta['saldo'] < 0 ? number_format(abs($cuenta['saldo']), 2) : '' }}</td>
                <td class="text-right">{{ $cuenta['saldo'] > 0 ? number_format($cuenta['saldo'], 2) : '' }}</td>
            </tr>
            @endforeach
            <tr>
                <td>UTILIDAD DEL EJERCICIO</td>
                <td></td>
                <td class="text-right">{{ number_format($utilidadEjercicio, 2) }}</td>
            </tr>
            <tr>
                <td><strong>TOTAL PATRIMONIO</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($totalPatrimonio + $utilidadEjercicio, 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>TOTAL PASIVOS Y PATRIMONIO</strong></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($totalPasivos + $totalPatrimonio + $utilidadEjercicio, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <table>
            <tr>
                <td width="40%"><strong>Total Activos:</strong></td>
                <td width="20%" class="text-right">{{ number_format($totalActivos, 2) }}</td>
                <td width="40%"><strong>Total Pasivos y Patrimonio:</strong></td>
                <td width="20%" class="text-right">{{ number_format($totalPasivos + $totalPatrimonio + $utilidadEjercicio, 2) }}</td>
            </tr>
            <tr>
                <td colspan="4" class="text-center {{ $totalActivos == ($totalPasivos + $totalPatrimonio + $utilidadEjercicio) ? 'text-green-600' : 'text-red-600' }}">
                    <strong>DIFERENCIA: {{ number_format($totalActivos - ($totalPasivos + $totalPatrimonio + $utilidadEjercicio), 2) }}</strong>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Documento generado el {{ $fechaGeneracion }} | Página 1 de 1</p>
    </div>
</body>
</html>
