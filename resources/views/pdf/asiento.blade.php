<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asiento Contable</title>
    <style>
        @page {
            margin: 0.5cm;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            /* BORDE EXTERIOR ELIMINADO: ya no hay borde negro alrededor de toda la hoja */
            border: none;
            padding: 0;           /* Sin padding extra para aprovechar el espacio */
            min-height: 100vh;
            margin: 0;
            background: #fff;
            margin: 20px
        }

        .container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }

        /* ========== ENCABEZADO SIN BORDES, SOLO ELEMENTOS VISUALES ========== */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            /* Sin bordes exteriores */
            border: none;
        }

        .header-table td {
            vertical-align: middle;
            padding: 6px 8px;
            /* Sin bordes en celdas */
            border: none;
        }

        .header-table td:nth-child(1) {
            width: 30%;
        }

        .header-table td:nth-child(2) {
            width: 40%;
            text-align: center;
        }

        .header-table td:nth-child(3) {
            width: 30%;
            text-align: right;
        }

        .empresa-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .empresa-logo {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 1px solid #ccc; /* borde sutil solo para logo si existe */
            object-fit: cover;
        }

        .empresa-text h1 {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .empresa-text .datos {
            font-size: 8px;
            color: #4b5563;
        }

        .titulo-principal h2 {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 2px;
            color: #1f2937;
        }

        .titulo-principal .numero-fecha {
            font-size: 9px;
            color: #4b5563;
        }

        .asiento-info .periodo {
            font-size: 9px;
            margin-bottom: 4px;
            color: #4b5563;
        }

        .badge-tipo {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 14px;
            font-size: 8px;
            font-weight: 600;
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
        }

        /* ========== INFORMACIÓN SECUNDARIA (SIN BORDES EXTERNOS) ========== */
        .info-secundaria {
            display: flex;
            width: 100%;
            margin-bottom: 8px;
            border-bottom: 1px solid #e5e7eb; /* única línea horizontal sutil */
            padding-bottom: 8px;
        }

        .info-secundaria .direccion {
            flex: 1;
            text-align: left;
            font-size: 9px;
            color: #2c3e50;
        }

        .info-secundaria .comprobante {
            flex: 1;
            text-align: right;
            font-size: 9px;
            color: #2c3e50;
        }

        /* ========== GLOSA CON LÍNEA INFERIOR SUAVE ========== */
        .glosa {
            margin: 8px 0 12px 0;
            padding: 6px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
            background-color: transparent;
            color: #1f2937;
        }

        /* ========== TABLA PRINCIPAL: SIN BORDES VERTICALES, SOLO HORIZONTALES ========== */
        .tabla-detalles {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 9px;
            /* page-break-inside: avoid; */
            border: none;
        }

        .tabla-detalles thead tr {
            border-bottom: 1px solid #cbd5e1;
        }

        .tabla-detalles th {
            border: none;
            border-bottom: 1px solid #9ca3af;
            padding: 8px 6px;
            font-weight: 700;
            text-align: left;
            font-size: 9px;
            background-color: #f9fafb;
            color: #111827;
        }

        .tabla-detalles td {
            border: none;
            border-bottom: 1px solid #e2e8f0; /* línea horizontal suave entre filas */
            padding: 8px 6px;
            vertical-align: top;
            background-color: #fff;
        }

        /* Última fila de totales: borde superior más marcado */
        .tabla-detalles tbody tr:last-child td {
            border-bottom: none;
        }


        .fila-totales td {
            border-top: 2px solid #9ca3af;
            border-bottom: none;
            background-color: #f3f4f6;
            font-weight: 700;
            padding: 8px 6px;
        }

        /* ANCHOS DE COLUMNAS - CODIGO MÁS ANCHO */
        .tabla-detalles th:nth-child(1),
        .tabla-detalles td:nth-child(1) {
            width: 12%;          /* Aumentado de 8% a 12% para evitar solapamiento */
        }

        .tabla-detalles th:nth-child(2),
        .tabla-detalles td:nth-child(2) {
            width: 20%;          /* Cuenta */
        }

        .tabla-detalles th:nth-child(3),
        .tabla-detalles td:nth-child(3) {
            width: 18%;          /* Centro Costo */
        }

        .tabla-detalles th:nth-child(4),
        .tabla-detalles td:nth-child(4) {
            width: 20%;          /* Descripción */
        }

        .tabla-detalles th:nth-child(5),
        .tabla-detalles td:nth-child(5) {
            width: 7%;           /* Deducible */
            text-align: center;
        }

        .tabla-detalles th:nth-child(6),
        .tabla-detalles td:nth-child(6) {
            width: 12%;          /* Debe */
            text-align: right;
        }

        .tabla-detalles th:nth-child(7),
        .tabla-detalles td:nth-child(7) {
            width: 11%;          /* Haber */
            text-align: right;
        }

        /* ========== AUDITORÍA: LÍNEA HORIZONTAL SUPERIOR SUTIL ========== */
        .auditoria {
            margin-top: 12px;
            padding: 8px 0 4px 0;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
            color: #4b5563;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .auditoria div {
            padding: 2px 0;
        }

        /* ========== RESUMENES CON BORDES SUAVES (SOLO CONTORNO LIGERO) ========== */
        .resumen {
            margin: 10px 0;
            padding: 8px 10px;
            border-radius: 6px;
            font-size: 9px;
            page-break-inside: avoid;
            background-color: #fefce8;
            border-left: 3px solid #eab308;
            box-shadow: none;
        }

        .resumen.centros-costo {
            background-color: #f0f9ff;
            border-left: 3px solid #3b82f6;
        }

        .resumen.deducible {
            background-color: #ecfdf5;
            border-left: 3px solid #10b981;
        }

        .resumen strong {
            font-weight: 700;
            color: #1e293b;
        }

        /* BADGES sin bordes agresivos */
        .badge-si {
            display: inline-block;
            padding: 2px 8px;
            background-color: #d1fae5;
            color: #065f46;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 600;
            border: none;
        }

        .badge-no {
            display: inline-block;
            padding: 2px 8px;
            background-color: #f1f5f9;
            color: #334155;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 500;
            border: none;
        }

        /* Estilos para tipos de asiento (badge) */
        .badge-ingreso {
            background-color: #dcfce7;
            border-color: #86efac;
            color: #166534;
        }

        .badge-egreso {
            background-color: #fee2e2;
            border-color: #fecaca;
            color: #991b1b;
        }

        .badge-traspaso {
            background-color: #e0f2fe;
            border-color: #b9e6fe;
            color: #075985;
        }

        .badge-ajuste {
            background-color: #fef9c3;
            border-color: #fde047;
            color: #854d0e;
        }

        .badge-cierre {
            background-color: #ede9fe;
            border-color: #c4b5fd;
            color: #5b21b6;
        }

        /* Utilidades */
        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .font-bold {
            font-weight: bold;
        }

        h3 {
            font-size: 11px;
            margin: 8px 0 4px 0;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: 0.3px;
        }

        /* separador sutil después del título detalles */
        .subtitulo-line {
            border-bottom: 1px solid #d4d4d8;
            margin-top: -2px;
            margin-bottom: 8px;
        }

        /* para mantener consistencia en centrado de badges deducible */
        .text-center .badge-si, .text-center .badge-no {
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- ENCABEZADO SIN BORDES (SOLO TIPOGRAFÍA) -->
        <table class="header-table">
            <tr>
                <td>
                    <div class="empresa-info">
                        <!-- logo condicional (no se usa borde alrededor) -->
@if(isset($logo_base64))
    <img src="{{ $logo_base64 }}" class="empresa-logo" alt="Logo">
@endif
                        <div class="empresa-text">
                            <h1>{{ $empresa->nombre ?? 'Empresa' }}</h1>
                            <div class="datos">
                                @if(isset($empresa) && $empresa->nit)
                                    <div>NIT: {{ $empresa->nit }}</div>
                                @endif
                                @if(isset($empresa) && $empresa->telefono)
                                    <div>Tel: {{ $empresa->telefono }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="titulo-principal">
                        <h2>ASIENTO CONTABLE</h2>
                        <div class="numero-fecha">
                            N° {{ $asiento->numero_asiento ?? '---' }} • {{ isset($asiento) && isset($formatDate) ? $formatDate($asiento->fecha) : (isset($asiento->fecha) ? $asiento->fecha : 'Fecha no disponible') }}
                        </div>
                    </div>
                </td>
                <td>
                    <div class="asiento-info">
                        <div class="periodo">
                            <strong>Periodo:</strong> {{ $asiento->periodo->nombre ?? 'N/A' }}
                        </div>
                        @php
                            $tipoMap = ['ingreso' => 'Ingreso', 'egreso' => 'Egreso', 'traspaso' => 'Traspaso', 'ajuste' => 'Ajuste', 'cierre' => 'Cierre'];
                            $tipoRaw = $asiento->tipo ?? 'ajuste';
                            $tipoTexto = $tipoMap[$tipoRaw] ?? ucfirst($tipoRaw);
                            $badgeClass = 'badge-tipo ';
                            if($tipoRaw == 'ingreso') $badgeClass .= 'badge-ingreso';
                            elseif($tipoRaw == 'egreso') $badgeClass .= 'badge-egreso';
                            elseif($tipoRaw == 'traspaso') $badgeClass .= 'badge-traspaso';
                            elseif($tipoRaw == 'ajuste') $badgeClass .= 'badge-ajuste';
                            elseif($tipoRaw == 'cierre') $badgeClass .= 'badge-cierre';
                        @endphp
                        <div class="{{ $badgeClass }}">
                            {{ $tipoTexto }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- INFORMACIÓN SECUNDARIA: líneas horizontales suaves en lugar de bordes de caja -->
        <div class="info-secundaria">
            <div class="direccion">
                <strong>Dirección:</strong><br>
                {{ $empresa->direccion ?? 'No especificada' }}<br>
                {{ $empresa->ciudad ?? '' }}{{ (isset($empresa->ciudad) && isset($empresa->pais) && $empresa->ciudad && $empresa->pais) ? ', ' : '' }}{{ $empresa->pais ?? '' }}
            </div>
            <div class="comprobante">
                <strong>Comprobante:</strong> {{ $asiento->comprobante ?? 'N/A' }}
            </div>
        </div>

        <!-- GLOSA con línea inferior en lugar de borde completo -->
        <div class="glosa">
            <strong>Glosa:</strong> {{ $asiento->glosa ?? 'Sin glosa registrada' }}
        </div>

        <!-- DETALLES DEL ASIENTO (tabla con solo separadores horizontales) -->
        <h3>Detalles del Asiento</h3>
        <div class="subtitulo-line"></div>
        <table class="tabla-detalles">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Cuenta</th>
                    <th>Centro Costo</th>
                    <th>Descripción</th>
                    <th>Deduc.</th>
                    <th>Debe</th>
                    <th>Haber</th>
                </tr>
            </thead>
            <tbody>
                @forelse($asiento->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->cuenta->codigo ?? '—' }}</td>
                    <td>{{ $detalle->cuenta->nombre ?? '—' }}</td>
                    <td>
                        @if(isset($detalle->centro_costo) && $detalle->centro_costo)
                            {{ $detalle->centro_costo->nombre ?? '' }}<br>
                            <small>({{ $detalle->centro_costo->codigo ?? '' }})</small>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $detalle->descripcion ?: '—' }}</td>
                    <td class="text-center">
                        @if(isset($detalle->deducible) && $detalle->deducible)
                            <span class="badge-si">Sí</span>
                        @else
                            <span class="badge-no">No</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if(isset($detalle->debe) && $detalle->debe > 0)
                            {{ isset($formatCurrency) ? $formatCurrency($detalle->debe) : number_format($detalle->debe, 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right">
                        @if(isset($detalle->haber) && $detalle->haber > 0)
                            {{ isset($formatCurrency) ? $formatCurrency($detalle->haber) : number_format($detalle->haber, 2) }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 12px;">No hay detalles registrados</td>
                </tr>
                @endforelse

                <!-- TOTALES (fila con borde superior más grueso, sin borde inferior) -->
                @php
                    $totalDebe = 0;
                    $totalHaber = 0;
                    if(isset($asiento->detalles)) {
                        foreach($asiento->detalles as $det) {
                            $totalDebe += $det->debe ?? 0;
                            $totalHaber += $det->haber ?? 0;
                        }
                    }
                @endphp
                <tr class="fila-totales">
                    <td colspan="5" class="text-right font-bold">TOTALES:</td>
                    <td class="text-right font-bold">{{ isset($formatCurrency) ? $formatCurrency($totalDebe) : number_format($totalDebe, 2) }}</td>
                    <td class="text-right font-bold">{{ isset($formatCurrency) ? $formatCurrency($totalHaber) : number_format($totalHaber, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- AUDITORÍA con línea superior sutil -->
        <div class="auditoria">
            <div>
                <strong>Creado por:</strong> {{ $asiento->creado_por->name ?? 'N/A' }} •
                {{ isset($asiento->created_at) ? \Carbon\Carbon::parse($asiento->created_at)->format('d/m/Y H:i') : 'Fecha no disponible' }}
            </div>
            @if(isset($asiento->updated_at) && isset($asiento->created_at) && $asiento->updated_at != $asiento->created_at)
                <div>
                    <strong>Modificado:</strong> {{ \Carbon\Carbon::parse($asiento->updated_at)->format('d/m/Y H:i') }}
                </div>
            @endif
        </div>

        <!-- RESUMENES: eliminado bordes de caja, solo bloques con color de fondo suave y línea lateral -->
        @php
            // Funciones auxiliares simuladas para entorno de plantilla (si no existen se manejan)
            $tieneCentros = false;
            $centrosArray = [];
            $totalDeducibleCalc = 0;
            if(isset($asiento->detalles)) {
                foreach($asiento->detalles as $det) {
                    if(isset($det->centro_costo) && $det->centro_costo && ($det->debe > 0 || $det->haber > 0)) {
                        $tieneCentros = true;
                        $codigo = $det->centro_costo->codigo ?? 'sin_codigo';
                        $monto = ($det->debe > 0) ? $det->debe : $det->haber;
                        if(!isset($centrosArray[$codigo])) $centrosArray[$codigo] = ['codigo' => $codigo, 'nombre' => $det->centro_costo->nombre ?? $codigo, 'total' => 0];
                        $centrosArray[$codigo]['total'] += $monto;
                    }
                    if(isset($det->deducible) && $det->deducible && ($det->debe > 0 || $det->haber > 0)) {
                        $totalDeducibleCalc += ($det->debe > 0) ? $det->debe : $det->haber;
                    }
                }
            }
        @endphp

        @if($tieneCentros && count($centrosArray) > 0)
            <div class="resumen centros-costo">
                <strong>📊 Distribución por Centros de Costos:</strong><br>
                @foreach($centrosArray as $idx => $centro)
                    {{ $centro['codigo'] }} ({{ $centro['nombre'] }}): {{ isset($formatCurrency) ? $formatCurrency($centro['total']) : number_format($centro['total'], 2) }}
                    @if(!$loop->last) &nbsp;|&nbsp; @endif
                @endforeach
            </div>
        @endif

        @if($totalDeducibleCalc > 0)
            <div class="resumen deducible">
                <div style="display: flex; justify-content: space-between; width: 100%;">
                    <div><strong>💰 Total gastos deducibles:</strong></div>
                    <div><strong>{{ isset($formatCurrency) ? $formatCurrency($totalDeducibleCalc) : number_format($totalDeducibleCalc, 2) }}</strong></div>
                </div>
            </div>
        @endif

        <!-- Pequeña nota: sin bordes de bloque, diseño limpio -->
        <div style="font-size: 7px; text-align: center; margin-top: 12px; color: #9ca3af; border-top: 1px solid #f0f0f0; padding-top: 6px;">
            Documento generado electrónicamente | Válido como comprobante contable
        </div>
    </div>
</body>
</html>
