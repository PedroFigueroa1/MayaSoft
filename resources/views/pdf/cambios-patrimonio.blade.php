<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }} - {{ $empresa->nombre }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 16px; font-weight: bold; }
        .empresa { font-weight: bold; font-size: 18px; }
        .periodo { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background-color: #f2f2f2; text-align: left; }
        .text-right { text-align: right; }
        .total { font-weight: bold; background-color: #f2f2f2; }
        .subtotal { font-weight: bold; }
        .fecha { text-align: right; margin-bottom: 20px; }
        .negative { color: red; }
        .cuenta-principal { font-weight: bold; }
        .cuenta-subnivel { padding-left: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="empresa">{{ $empresa->nombre }}</div>
        <div class="title">{{ $titulo }}</div>
        <div class="periodo">Período: {{ $periodo->nombre }}</div>
    </div>

    <div class="fecha">Fecha de generación: {{ $fechaGeneracion }}</div>

    <table>
        <thead>
            <tr>
                <th width="40%">Cuenta de Patrimonio</th>
                <th width="20%">Saldo Inicial</th>
                <th width="20%">Movimientos</th>
                <th width="20%">Saldo Final</th>
            </tr>
        </thead>
        <tbody>
            @foreach($patrimonio as $cuenta)
                <tr class="cuenta-principal">
                    <td>{{ $cuenta->nombre }}</td>
                    <td class="text-right">{{ number_format($cuenta->saldo_inicial, 2) }}</td>
                    <td class="text-right">{{ number_format($cuenta->movimientos, 2) }}</td>
                    <td class="text-right">{{ number_format($cuenta->saldo_final, 2) }}</td>
                </tr>
                @foreach($cuenta->hijos as $hijo)
                    <tr class="cuenta-subnivel">
                        <td>{{ $hijo->nombre }}</td>
                        <td class="text-right">{{ number_format($hijo->saldo_inicial, 2) }}</td>
                        <td class="text-right">{{ number_format($hijo->movimientos, 2) }}</td>
                        <td class="text-right">{{ number_format($hijo->saldo_final, 2) }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <table>
        <tr class="total">
            <td>UTILIDAD DEL PERÍODO</td>
            <td colspan="2"></td>
            <td class="text-right {{ $utilidad < 0 ? 'negative' : '' }}">
                {{ number_format($utilidad, 2) }}
            </td>
        </tr>
        <tr class="total">
            <td>TOTAL PATRIMONIO</td>
            <td class="text-right">{{ number_format($saldoInicialTotal, 2) }}</td>
            <td class="text-right">{{ number_format($movimientosTotal, 2) }}</td>
            <td class="text-right">{{ number_format($saldoFinalTotal, 2) }}</td>
        </tr>
    </table>
</body>
</html>
