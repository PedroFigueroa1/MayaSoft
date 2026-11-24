<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Libro Mayor - {{ $cuenta->codigo }} - {{ $cuenta->nombre }}</title>
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
        .page-break {
            page-break-after: always;
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
        <h1>LIBRO MAYOR</h1>
    </div>

    <div class="company-info">
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
                <td><strong>Cuenta:</strong></td>
                <td>{{ $cuenta->codigo }} - {{ $cuenta->nombre }}</td>
                <td><strong>Tipo de Cuenta:</strong></td>
                <td>
                    @switch($cuenta->tipo)
                        @case('A') Activo @break
                        @case('P') Pasivo @break
                        @case('PN') Patrimonio Neto @break
                        @case('I') Ingreso @break
                        @case('G') Gasto @break
                    @endswitch
                </td>
            </tr>
            <tr>
                <td><strong>Rango de Fechas:</strong></td>
                <td colspan="3">
                    {{ $fechaInicio ? date('d/m/Y', strtotime($fechaInicio)) : date('d/m/Y', strtotime($periodo->fecha_inicio)) }}
                    al
                    {{ $fechaFin ? date('d/m/Y', strtotime($fechaFin)) : date('d/m/Y', strtotime($periodo->fecha_cierre)) }}
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th width="10%">Fecha</th>
                <th width="10%">N° Asiento</th>
                <th width="40%">Glosa</th>
                <th width="10%" class="text-right">Debe</th>
                <th width="10%" class="text-right">Haber</th>
                <th width="10%" class="text-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <!-- Saldo Inicial -->
            <tr>
                <td colspan="3" class="text-right"><strong>Saldo Inicial</strong></td>
                <td colspan="2"></td>
                <td class="text-right"><strong>{{ number_format($saldoInicial, 2) }}</strong></td>
            </tr>

            <!-- Movimientos -->
            @php
                $saldoAcumulado = $saldoInicial;
            @endphp

            @foreach($movimientos as $movimiento)
                @php
                    if ($cuenta->tipo == 'A' || $cuenta->tipo == 'G') {
                        $saldoAcumulado += $movimiento['debe'] - $movimiento['haber'];
                    } else {
                        $saldoAcumulado += $movimiento['haber'] - $movimiento['debe'];
                    }
                @endphp
                <tr>
                    <td>{{ date('d/m/Y', strtotime($movimiento['fecha'])) }}</td>
                    <td>{{ $movimiento['numero_asiento'] }}</td>
                    <td>{{ $movimiento['glosa'] }}</td>
                    <td class="text-right">{{ number_format($movimiento['debe'], 2) }}</td>
                    <td class="text-right">{{ number_format($movimiento['haber'], 2) }}</td>
                    <td class="text-right">{{ number_format($saldoAcumulado, 2) }}</td>
                </tr>
            @endforeach

            <!-- Totales -->
            <tr style="background-color: #f0f0f0;">
                <td colspan="3" class="text-right"><strong>TOTALES</strong></td>
                <td class="text-right"><strong>{{ number_format($totalDebe, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalHaber, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($saldoFinal, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <table>
            <tr>
                <td width="25%"><strong>Saldo Inicial:</strong></td>
                <td width="25%" class="text-right">{{ number_format($saldoInicial, 2) }}</td>
                <td width="25%"><strong>Saldo Final:</strong></td>
                <td width="25%" class="text-right">{{ number_format($saldoFinal, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Total Débitos:</strong></td>
                <td class="text-right">{{ number_format($totalDebe, 2) }}</td>
                <td><strong>Total Créditos:</strong></td>
                <td class="text-right">{{ number_format($totalHaber, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Documento generado el {{ $fechaGeneracion }} | Página 1 de 1</p>
    </div>
</body>
</html>
