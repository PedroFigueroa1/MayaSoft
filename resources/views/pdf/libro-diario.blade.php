<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libro Diario - {{ $periodo->nombre }}</title>
    <style>
        /* Estilos generales */
        @page {
            margin: 1cm;
            size: A4 landscape;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            border: 2px solid #000;
            padding: 15px;
            margin: 0;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }

        /* Encabezado */
        .header {
            border: 1px solid #000;
            margin-bottom: 15px;
            background-color: #fff;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 15px;
            border-bottom: 1px solid #000;
        }

        .empresa-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .empresa-logo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid #000;
            object-fit: cover;
        }

        .empresa-text h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 5px 0;
        }

        .empresa-text p {
            font-size: 10px;
            margin: 2px 0;
            color: #666;
        }

        .titulo h2 {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 5px 0;
            text-align: right;
        }

        .titulo p {
            font-size: 10px;
            margin: 2px 0;
            text-align: right;
            color: #666;
        }

        /* Resumen */
        .resumen-periodo {
            border: 1px solid #000;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f9f9f9;
        }

        .resumen-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .resumen-titulo {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
        }

        .resumen-totales {
            display: flex;
            gap: 20px;
        }

        .resumen-item {
            text-align: center;
        }

        .resumen-item p {
            font-size: 9px;
            margin: 0 0 3px 0;
            color: #666;
        }

        .resumen-item .valor {
            font-size: 14px;
            font-weight: bold;
        }

        .text-blue { color: #2563eb; }
        .text-green { color: #16a34a; }
        .text-red { color: #dc2626; }

        /* Información de control */
        .control-info {
            border: 1px solid #000;
            margin-bottom: 15px;
            padding: 8px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #fff;
            font-size: 10px;
        }

        /* Tabla principal */
        .tabla-container {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 15px;
            border: 1px solid #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        th {
            background-color: #e5e7eb;
            border: 1px solid #000;
            padding: 8px 4px;
            text-align: left;
            font-weight: 600;
            font-size: 9px;
        }

        td {
            border: 1px solid #000;
            padding: 6px 4px;
            vertical-align: top;
        }

        /* Estilos específicos para columnas */
        .col-fecha { width: 7%; }
        .col-asiento { width: 7%; }
        .col-tipo { width: 5%; }
        .col-cuenta { width: 18%; }
        .col-centro { width: 12%; }
        .col-descripcion { width: 23%; }
        .col-deducible { width: 5%; text-align: center; }
        .col-debe { width: 8%; text-align: right; }
        .col-haber { width: 8%; text-align: right; }

        .font-mono {
            font-family: 'Courier New', monospace;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: 600;
            border: 1px solid;
        }

        .badge-ingreso {
            background-color: #d1fae5;
            border-color: #065f46;
            color: #065f46;
        }

        .badge-egreso {
            background-color: #fee2e2;
            border-color: #7f1d1d;
            color: #7f1d1d;
        }

        .badge-traspaso {
            background-color: #dbeafe;
            border-color: #1e40af;
            color: #1e40af;
        }

        .badge-ajuste {
            background-color: #fef3c7;
            border-color: #92400e;
            color: #92400e;
        }

        .badge-cierre {
            background-color: #e9d5ff;
            border-color: #5b21b6;
            color: #5b21b6;
        }

        /* Deducible */
        .deducible-si {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            background-color: #d1fae5;
            border: 1px solid #065f46;
            border-radius: 12px;
            color: #065f46;
            font-size: 8px;
            font-weight: 600;
        }

        .deducible-no {
            display: inline-block;
            padding: 2px 6px;
            background-color: #f3f4f6;
            border: 1px solid #374151;
            border-radius: 12px;
            color: #374151;
            font-size: 8px;
            font-weight: 600;
        }

        /* Fila de totales */
        .fila-totales {
            background-color: #d1d5db;
            font-weight: bold;
        }

        .fila-totales td {
            padding: 8px 4px;
        }

        /* Pie de página */
        .footer {
            border: 1px solid #000;
            padding: 8px;
            font-size: 8px;
            color: #666;
            text-align: center;
            margin-top: 15px;
        }

        /* Montos */
        .monto-debe {
            color: #dc2626;
            font-weight: 500;
        }

        .monto-haber {
            color: #16a34a;
            font-weight: 500;
        }

        .monto-guion {
            color: #9ca3af;
        }

        /* Hover effect solo para pantalla */
        @media screen {
            tr:hover {
                background-color: #f9fafb;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Encabezado con datos de la empresa -->
        <div class="header">
            <div class="header-content">
                <div class="empresa-info">
                    @if(!empty($empresa['logo']))
                        <img src="{{ url('/imagenes/logo/' . $empresa->logo) }}"
                             alt="Logo de {{ $empresa['nombre'] }}"
                             class="empresa-logo">
                    @endif
                    <div class="empresa-text">
                        <h1>{{ $empresa['nombre'] }}</h1>
                        <p>{{ isset($empresa['nit']) && $empresa['nit'] ? "NIT: " . $empresa['nit'] : 'NIT: No especificado' }}</p>
                    </div>
                </div>
                <div class="titulo">
                    <h2>LIBRO DIARIO</h2>
                    <p>Período: {{ $periodo->nombre }}</p>
                    <p>
                        {{ \Carbon\Carbon::parse($periodo->fecha_inicio)->format('d/m/Y') }}
                        -
                        {{ \Carbon\Carbon::parse($periodo->fecha_cierre)->format('d/m/Y') }}
                    </p>
                </div>
            </div>

            <!-- Dirección y contacto -->
            <div style="padding: 8px 15px; border-top: 1px solid #000; font-size: 9px;">
                <p style="margin: 0;">
                    {{ $empresa['direccion'] ?: 'Dirección no especificada' }}
                    {{ isset($empresa['telefono']) && $empresa['telefono'] ? " | Tel: " . $empresa['telefono'] : '' }}
                    {{ isset($empresa['ciudad']) && $empresa['ciudad'] ? " | " . $empresa['ciudad'] : '' }}
                    {{ isset($empresa['pais']) && $empresa['pais'] ? ", " . $empresa['pais'] : '' }}
                </p>
            </div>
        </div>

        <!-- Resumen del período -->
        <div class="resumen-periodo">
            <div class="resumen-header">
                <p class="resumen-titulo">Resumen del Período</p>
                <div class="resumen-totales">
                    <div class="resumen-item">
                        <p>Total Debe</p>
                        <p class="valor text-blue">{{ number_format($totalDebe, 2, ',', '.') }}</p>
                    </div>
                    <div class="resumen-item">
                        <p>Total Haber</p>
                        <p class="valor text-green">{{ number_format($totalHaber, 2, ',', '.') }}</p>
                    </div>
                    <div class="resumen-item">
                        <p>Diferencia</p>
                        <p class="valor {{ $totalDebe == $totalHaber ? 'text-green' : 'text-red' }}">
                            {{ number_format(abs($totalDebe - $totalHaber), 2, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de control -->
        <div class="control-info">
            <div>
                <span>Mostrando {{ count($asientos) }} asientos contables</span>
            </div>
            <div>
                <span>Generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>

        <!-- Tabla de asientos -->
        <div class="tabla-container">
            <table>
                <thead>
                    <tr>
                        <th class="col-fecha">Fecha</th>
                        <th class="col-asiento">Asiento</th>
                        <th class="col-tipo">Tipo</th>
                        <th class="col-cuenta">Cuenta</th>
                        <th class="col-centro">Centro Costo</th>
                        <th class="col-descripcion">Descripción</th>
                        <th class="col-deducible">Deduc.</th>
                        <th class="col-debe">Debe</th>
                        <th class="col-haber">Haber</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($asientos as $asiento)
                        @foreach($asiento->detalles as $index => $detalle)
                            <tr>
                                <!-- Fecha (solo en primera fila del asiento) -->
                                @if($index === 0)
                                    <td rowspan="{{ count($asiento->detalles) }}" style="vertical-align: middle;">
                                        {{ \Carbon\Carbon::parse($asiento->fecha)->format('d/m/Y') }}
                                    </td>
                                    <td rowspan="{{ count($asiento->detalles) }}" style="vertical-align: middle;">
                                        {{ $asiento->numero_asiento }}
                                    </td>
                                    <td rowspan="{{ count($asiento->detalles) }}" style="vertical-align: middle;">
                                        @php
                                            $tipoMap = [
                                                'ING' => 'Ingreso',
                                                'EGR' => 'Egreso',
                                                'TRN' => 'Traspaso',
                                                'AJT' => 'Ajuste',
                                                'CIE' => 'Cierre'
                                            ];
                                            $tipoTexto = $tipoMap[$asiento->tipo] ?? $asiento->tipo;
                                            $tipoLower = strtolower($tipoMap[$asiento->tipo] ?? $asiento->tipo);
                                        @endphp
                                        <span class="badge badge-{{ $tipoLower }}">
                                            {{ $tipoTexto }}
                                        </span>
                                    </td>
                                @endif

                                <!-- Cuenta -->
                                <td>
                                    @if($detalle->cuenta)
                                        <span class="font-mono">{{ $detalle->cuenta->codigo }}</span>
                                        <div style="font-size: 8px;">{{ $detalle->cuenta->nombre }}</div>
                                    @else
                                        <span style="color: #ef4444;">Cuenta no disponible</span>
                                    @endif
                                </td>

                                <!-- Centro de Costos -->
                                <td>
                                    @if($detalle->centro_costo)
                                        <span class="font-mono" style="font-size: 8px;">{{ $detalle->centro_costo->codigo }}</span>
                                        <div style="font-size: 8px;">{{ $detalle->centro_costo->nombre }}</div>
                                    @else
                                        <span style="color: #9ca3af;">-</span>
                                    @endif
                                </td>

                                <!-- Descripción -->
                                <td>
                                    <div style="font-weight: 500;">{{ $asiento->glosa }}</div>
                                    @if($detalle->descripcion)
                                        <div style="font-size: 8px; color: #666;">{{ $detalle->descripcion }}</div>
                                    @endif
                                </td>

                                <!-- Deducible -->
                                <td class="text-center">
                                    @if($detalle->deducible)
                                        <span class="deducible-si">
                                            <span style="margin-right: 2px;">✓</span> Sí
                                        </span>
                                    @else
                                        <span class="deducible-no">No</span>
                                    @endif
                                </td>

                                <!-- Debe -->
                                <td class="text-right">
                                    @if($detalle->debe > 0)
                                        <span class="monto-debe font-mono">{{ number_format($detalle->debe, 2, ',', '.') }}</span>
                                    @else
                                        <span class="monto-guion">-</span>
                                    @endif
                                </td>

                                <!-- Haber -->
                                <td class="text-right">
                                    @if($detalle->haber > 0)
                                        <span class="monto-haber font-mono">{{ number_format($detalle->haber, 2, ',', '.') }}</span>
                                    @else
                                        <span class="monto-guion">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach

                    <!-- Totales Generales -->
                    <tr class="fila-totales">
                        <td colspan="7" class="text-right">TOTALES GENERALES</td>
                        <td class="text-right font-mono" style="color: #2563eb;">{{ number_format($totalDebe, 2, ',', '.') }}</td>
                        <td class="text-right font-mono" style="color: #16a34a;">{{ number_format($totalHaber, 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pie de página -->
        <div class="footer">
            <p style="margin: 0;">Generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }} | Sistema Contable</p>
        </div>
    </div>
</body>
</html>
