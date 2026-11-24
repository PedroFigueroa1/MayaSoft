<?php

namespace App\Http\Controllers;

use App\Models\Periodo;
use App\Models\Cuentas;
use App\Models\DetalleAsiento;
use App\Models\Asiento;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\FlujoEfectivoExport;

class FlujoEfectivoController extends Controller
{
    public function generar(Periodo $periodo)
    {
        $data = $this->calcularFlujo($periodo);

        return Inertia::render('FlujoEfectivo/Reporte', array_merge($data, [
            'periodo' => $periodo,
            'empresa' => $periodo->empresa,
        ]));
    }

    public function exportarExcel(Request $request)
    {
        $request->validate(['periodo_id' => 'required|exists:periodos,id']);
        $periodo = Periodo::findOrFail($request->periodo_id);
        $data    = $this->calcularFlujo($periodo);

        $filename = (new FlujoEfectivoExport(
            origenAplicacion:          $data['origenAplicacion'],
            actividadesOperativas:     $data['actividadesOperativas'],
            actividadesInversion:      $data['actividadesInversion'],
            actividadesFinanciamiento: $data['actividadesFinanciamiento'],
            totalOperativas:           $data['totalOperativas'],
            totalInversion:            $data['totalInversion'],
            totalFinanciamiento:       $data['totalFinanciamiento'],
            flujoNeto:                 $data['flujoNeto'],
            saldoInicial:              $data['saldoInicial'],
            saldoFinal:                $data['saldoFinal'],
            periodo:                   $periodo,
            empresa:                   $periodo->empresa,
        ))->generate();

        $downloadUrl = URL::temporarySignedRoute(
            'flujo-efectivo.download-excel',
            now()->addMinutes(30),
            ['filename' => $filename]
        );

        return response()->json([
            'download_url' => $downloadUrl,
            'filename'     => $filename,
        ]);
    }

    public function downloadExcel(Request $request, string $filename)
    {
        if (!$request->hasValidSignature()) abort(401);

        $filename = basename($filename);
        $path     = storage_path('app/temp/' . $filename);

        if (!file_exists($path)) abort(404);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ================================================================
    // NÚCLEO: calcula todo el flujo
    // ================================================================

    private function calcularFlujo(Periodo $periodo): array
    {
        // 1. Saldos iniciales desde asiento de apertura
        $saldosIniciales = $this->obtenerSaldosIniciales($periodo);

        // 2. Saldos finales desde todos los movimientos del período
        $saldosFinales = $this->obtenerSaldosFinales($periodo->id);

        // 3. Construir tabla origen/aplicación (balance general, sin filtrar ajustes)
        $origenAplicacion = $this->construirOrigenAplicacion($saldosIniciales, $saldosFinales);

        // 3.b NUEVO: montos de revalorización por inflación (asientos "de ajuste")
        //     que NO representan movimiento real de efectivo, para poder excluirlos
        //     de Inversión y Financiamiento (su efecto ya se revierte en Operativas).
        $ajustesInflacion = $this->obtenerAjustesInflacion($periodo->id);

        // 4. Resultado de la gestión
        $resultadoGestion = $this->calcularResultadoGestion($periodo->id);

        // 4.b FIX #4: "Utilidad de la Gestión" no es una cuenta que se alimente
        //     de asientos durante el año (es un cálculo), así que la inyectamos
        //     directamente en el cuadro de Origen y Aplicación: saldo inicial 0
        //     (lo que había del año pasado ya se traspasó a Resultados Acumulados
        //     en el saldo inicial) y saldo final = resultado de ESTA gestión.
        $origenAplicacion = $this->ajustarUtilidadGestion($origenAplicacion, $resultadoGestion);

        // 5. Actividades operativas (método indirecto)
        $actividadesOperativas = $this->construirOperativas(
            $resultadoGestion,
            $origenAplicacion
        );

        // 6. Actividades de inversión (activos no corrientes), excluyendo revalorización
        $actividadesInversion = $this->construirInversion($origenAplicacion, $ajustesInflacion);

        // 7. Actividades de financiamiento (patrimonio neto), excluyendo revalorización
        $actividadesFinanciamiento = $this->construirFinanciamiento($origenAplicacion, $ajustesInflacion);

        // 8. Saldo inicial y final de efectivo
        $saldoInicial = $this->calcularEfectivo($saldosIniciales);
        $saldoFinal   = $this->calcularEfectivo($saldosFinales);

        $totalOperativas     = $this->sumarActividades($actividadesOperativas);
        $totalInversion      = $this->sumarActividades($actividadesInversion);
        $totalFinanciamiento = $this->sumarActividades($actividadesFinanciamiento);
        $flujoNeto           = $totalOperativas + $totalInversion + $totalFinanciamiento;

        return compact(
            'origenAplicacion',
            'actividadesOperativas',
            'actividadesInversion',
            'actividadesFinanciamiento',
            'totalOperativas',
            'totalInversion',
            'totalFinanciamiento',
            'flujoNeto',
            'saldoInicial',
            'saldoFinal',
        );
    }

    // ================================================================
    // SALDOS INICIALES — desde el asiento de apertura de la gestión
    // ================================================================

    private function obtenerSaldosIniciales(Periodo $periodo): array
    {
        // FIX #4 (parte 1): antes solo se tomaba el PRIMER asiento que matcheaba
        // (->first()), y el patrón de detección (glosa 'apertura' / numero_asiento
        // 'CT-001%') no capturaba asientos como el traspaso de la utilidad del
        // año anterior a Resultados Acumulados (ej. 'CT-0003', glosa "Traspaso
        // resultados 2024"). Ese asiento SÍ forma parte de la estructura de
        // saldos iniciales de la gestión (se contabiliza el mismo día en que
        // arranca el período), así que ahora tomamos TODOS los asientos tipo
        // Traspaso/Cierre fechados en el inicio del período, no solo uno.
        $query = Asiento::where('periodo_id', $periodo->id)
            ->where(function($q) {
                $q->where('tipo', 'CIE')
                  ->orWhere('tipo', 'TRN');
            });

        if (!empty($periodo->fecha_inicio)) {
            // Restringimos a los asientos fechados al inicio del período para
            // no arrastrar traspasos posteriores (ej. traspasos de costos a
            // producción en proceso, que también son tipo 'Traspaso').
            $query->whereDate('fecha', $periodo->fecha_inicio);
        } else {
            // Fallback si el período no tiene fecha_inicio: usamos el patrón
            // anterior (glosa/numero de asiento) como antes.
            $query->where(function($q3) {
                $q3->where('glosa', 'like', '%apertura%')
                   ->orWhere('glosa', 'like', '%Apertura%')
                   ->orWhere('glosa', 'like', '%Traspaso resultados%')
                   ->orWhere('numero_asiento', 'like', 'CT-001%')
                   ->orWhere('numero_asiento', 'like', 'CI-001%');
            });
        }

        $asientosApertura = $query->get();

        if ($asientosApertura->isEmpty()) return [];

        $detalles = DetalleAsiento::whereIn('asiento_id', $asientosApertura->pluck('id'))
            ->with('cuenta')
            ->get();

        $saldos = [];
        foreach ($detalles as $d) {
            if (!$d->cuenta) continue;
            $cuentaId = $d->cuenta_id;
            if (!isset($saldos[$cuentaId])) {
                $saldos[$cuentaId] = [
                    'cuenta_id'   => $cuentaId,
                    'codigo'      => $d->cuenta->codigo,
                    'nombre'      => $d->cuenta->nombre,
                    'tipo'        => $d->cuenta->tipo,
                    'saldo'       => 0,
                ];
            }
            if (in_array($d->cuenta->tipo, ['A', 'G'])) {
                $saldos[$cuentaId]['saldo'] += $d->debe - $d->haber;
            } else {
                $saldos[$cuentaId]['saldo'] += $d->haber - $d->debe;
            }
        }

        return $saldos;
    }

    // ================================================================
    // SALDOS FINALES — acumulado de todos los movimientos del período
    // ================================================================

    private function obtenerSaldosFinales(int $periodoId): array
    {
        $resultados = DetalleAsiento::select(
                'detalle_asientos.cuenta_id',
                DB::raw('SUM(detalle_asientos.debe) as total_debe'),
                DB::raw('SUM(detalle_asientos.haber) as total_haber')
            )
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->where('asientos.periodo_id', $periodoId)
            ->groupBy('detalle_asientos.cuenta_id')
            ->with('cuenta')
            ->get();

        $saldos = [];
        foreach ($resultados as $r) {
            if (!$r->cuenta) continue;
            $tipo  = $r->cuenta->tipo;
            $saldo = in_array($tipo, ['A', 'G'])
                ? $r->total_debe - $r->total_haber
                : $r->total_haber - $r->total_debe;

            $saldos[$r->cuenta_id] = [
                'cuenta_id' => $r->cuenta_id,
                'codigo'    => $r->cuenta->codigo,
                'nombre'    => $r->cuenta->nombre,
                'tipo'      => $tipo,
                'saldo'     => $saldo,
            ];
        }

        return $saldos;
    }

    // ================================================================
    // TABLA ORIGEN / APLICACIÓN (se mantiene igual: balance general puro)
    // ================================================================

    private function construirOrigenAplicacion(array $saldosIni, array $saldosFin): array
    {
        $todasCuentas = array_unique(array_merge(
            array_keys($saldosIni),
            array_keys($saldosFin)
        ));

        $tabla = [];

        foreach ($todasCuentas as $cuentaId) {
            $ini    = $saldosIni[$cuentaId]['saldo'] ?? 0;
            $fin    = $saldosFin[$cuentaId]['saldo'] ?? 0;
            $tipo   = $saldosFin[$cuentaId]['tipo']   ?? ($saldosIni[$cuentaId]['tipo'] ?? 'A');
            $nombre = $saldosFin[$cuentaId]['nombre'] ?? ($saldosIni[$cuentaId]['nombre'] ?? '');
            $codigo = $saldosFin[$cuentaId]['codigo'] ?? ($saldosIni[$cuentaId]['codigo'] ?? '');

            $variacion = $fin - $ini;

            $origen     = 0;
            $aplicacion = 0;

            if (in_array($tipo, ['A'])) {
                if ($variacion < 0) $origen     = abs($variacion);
                if ($variacion > 0) $aplicacion = abs($variacion);
            } elseif (in_array($tipo, ['P', 'PN'])) {
                if ($variacion > 0) $origen     = abs($variacion);
                if ($variacion < 0) $aplicacion = abs($variacion);
            }

            if ($variacion != 0 || $ini != 0 || $fin != 0) {
                $tabla[$cuentaId] = [
                    'cuenta_id'  => $cuentaId,
                    'codigo'     => $codigo,
                    'nombre'     => $nombre,
                    'tipo'       => $tipo,
                    'saldo_ini'  => $ini,
                    'saldo_fin'  => $fin,
                    'variacion'  => $variacion,
                    'origen'     => $origen,
                    'aplicacion' => $aplicacion,
                ];
            }
        }

        uasort($tabla, fn($a, $b) => strcmp($a['codigo'], $b['codigo']));

        return array_values($tabla);
    }

    // ================================================================
    // FIX #4: inyecta la utilidad DE ESTA GESTIÓN en el cuadro de
    // Origen y Aplicación. No es una cuenta que reciba asientos durante
    // el año (solo se postea al cierre), así que su saldo final es
    // simplemente el resultado ya calculado (ingresos - gastos), y su
    // saldo inicial siempre es 0 (lo del año anterior ya se trasladó a
    // Resultados Acumulados dentro de obtenerSaldosIniciales).
    // ================================================================

    private function ajustarUtilidadGestion(array $origenAplicacion, float $resultadoGestion): array
    {
        foreach ($origenAplicacion as $i => $fila) {
            if ($fila['tipo'] === 'PN' && stripos($fila['nombre'], 'UTILIDAD DE LA GESTI') !== false) {
                $origenAplicacion[$i]['saldo_ini']  = 0;
                $origenAplicacion[$i]['saldo_fin']  = $resultadoGestion;
                $origenAplicacion[$i]['variacion']  = $resultadoGestion;
                $origenAplicacion[$i]['origen']     = $resultadoGestion > 0 ? $resultadoGestion : 0;
                $origenAplicacion[$i]['aplicacion'] = $resultadoGestion < 0 ? abs($resultadoGestion) : 0;
                return $origenAplicacion;
            }
        }

        // Si la cuenta no existía todavía en la tabla (ej. primer período
        // con ganancia), la agregamos.
        if ($resultadoGestion != 0) {
            $origenAplicacion[] = [
                'cuenta_id'  => null,
                'codigo'     => '3.1.4',
                'nombre'     => 'UTILIDAD DE LA GESTIÓN',
                'tipo'       => 'PN',
                'saldo_ini'  => 0,
                'saldo_fin'  => $resultadoGestion,
                'variacion'  => $resultadoGestion,
                'origen'     => $resultadoGestion > 0 ? $resultadoGestion : 0,
                'aplicacion' => $resultadoGestion < 0 ? abs($resultadoGestion) : 0,
            ];
        }

        return $origenAplicacion;
    }

    // ================================================================
    // NUEVO: montos de revalorización por inflación (no monetarios)
    // ================================================================
    //
    // Identifica los asientos que tocan las cuentas "AJUSTE POR INFLACIÓN
    // Y TENENCIA DE BIENES" (Ingreso o Gasto) y suma, por cada cuenta
    // contraparte (activos fijos, patrimonio, etc.), el monto neto de
    // esos asientos. Ese monto es 100% no-monetario y debe excluirse de
    // Inversión y Financiamiento; su efecto ya se revierte en Operativas
    // a través del ajuste de inflación (ver construirOperativas).
    //
    private function obtenerAjustesInflacion(int $periodoId): array
    {
        $cuentaAjusteIds = Cuentas::where(function ($q) {
                $q->where('nombre', 'like', '%AJUSTE POR INFLACI%')
                  ->orWhere('nombre', 'like', '%TENENCIA DE BIENES%');
            })
            ->pluck('id');

        if ($cuentaAjusteIds->isEmpty()) {
            return [];
        }

        $asientoIds = DetalleAsiento::whereIn('cuenta_id', $cuentaAjusteIds)
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->where('asientos.periodo_id', $periodoId)
            ->pluck('asientos.id')
            ->unique();

        if ($asientoIds->isEmpty()) {
            return [];
        }

        $lineas = DetalleAsiento::whereIn('asiento_id', $asientoIds)
            ->with('cuenta')
            ->get();

        $montos = [];
        foreach ($lineas as $l) {
            if (!$l->cuenta) continue;
            // No nos interesa la propia cuenta de ajuste, solo la contraparte
            if ($cuentaAjusteIds->contains($l->cuenta_id)) continue;

            $tipo  = $l->cuenta->tipo;
            $monto = in_array($tipo, ['A', 'G'])
                ? $l->debe - $l->haber
                : $l->haber - $l->debe;

            $montos[$l->cuenta_id] = ($montos[$l->cuenta_id] ?? 0) + $monto;
        }

        return $montos; // cuenta_id => variación atribuible solo a revalorización
    }

    // ================================================================
    // ACTIVIDADES OPERATIVAS (método indirecto)
    // ================================================================

    private function construirOperativas(float $resultado, array $origenAplicacion): array
    {
        $actividades = [];

        // 1. Resultado de la gestión
        $actividades[] = [
            'concepto'  => 'Resultado de la Gestión',
            'monto'     => $resultado,
            'es_ajuste' => false,
            'tipo'      => $resultado >= 0 ? 'entrada' : 'salida',
        ];

        // 2. FIX #1: Ajuste por depreciaciones/amortizaciones
        //    La depreciación acumulada es un activo de saldo contrario:
        //    cuando aumenta, construirOrigenAplicacion la marca como
        //    "origen" (no "aplicación"). Antes el código solo miraba
        //    "aplicacion", por eso la depreciación nunca aparecía.
        $depreciaciones = array_filter($origenAplicacion, fn($r) =>
            $r['tipo'] === 'A' &&
            (stripos($r['nombre'], 'DEPRECIA') !== false ||
             stripos($r['nombre'], 'AMORTIZA') !== false)
        );
        foreach ($depreciaciones as $d) {
            $monto = $d['origen'] > 0 ? $d['origen'] : $d['aplicacion'];
            if ($monto == 0) continue;
            $actividades[] = [
                'concepto'  => $d['nombre'],
                'monto'     => $monto,
                'es_ajuste' => true,
                // Si aumentó la depreciación acumulada (origen > 0) es un
                // gasto no monetario que hay que sumar de vuelta (entrada).
                'tipo'      => $d['origen'] > 0 ? 'entrada' : 'salida',
            ];
        }

        // 3. FIX #2: Ajuste por inflación / mantenimiento de valor / tenencia de bienes
        //    Hay cuentas de Ingreso y de Gasto con el mismo tipo de nombre.
        //    - Ingreso (ganancia no monetaria, ya sumada al resultado)  -> restar (salida)
        //    - Gasto   (pérdida no monetaria, ya restada del resultado) -> sumar  (entrada)
        $ajustes = array_filter($origenAplicacion, fn($r) =>
            stripos($r['nombre'], 'AJUSTE POR INFLACIÓN') !== false ||
            stripos($r['nombre'], 'AJUSTE POR INFLACION') !== false ||
            stripos($r['nombre'], 'MANTENIMIENTO DE VALOR') !== false ||
            stripos($r['nombre'], 'TENENCIA DE BIENES') !== false
        );
        foreach ($ajustes as $a) {
            if ($a['variacion'] == 0) continue;

            if ($a['tipo'] === 'I') {
                // Ganancia no monetaria: se revierte restando
                $tipoFlujo = $a['variacion'] > 0 ? 'salida' : 'entrada';
            } else {
                // Gasto no monetario (o cualquier otro tipo): se revierte sumando
                $tipoFlujo = $a['variacion'] > 0 ? 'entrada' : 'salida';
            }

            $actividades[] = [
                'concepto'  => $a['nombre'],
                'monto'     => abs($a['variacion']),
                'es_ajuste' => true,
                'tipo'      => $tipoFlujo,
            ];
        }

        // 4. Variaciones de activo corriente (excluye efectivo y depreciaciones)
        $excluirActivo = ['CAJA', 'BANCO', 'EFECTIVO', 'DEPRECIA', 'AMORTIZA',
                          'AJUSTE POR INFLACIÓN', 'AJUSTE POR INFLACION'];
        $activoCorriente = array_filter($origenAplicacion, function($r) use ($excluirActivo) {
            if ($r['tipo'] !== 'A') return false;
            if (!str_starts_with($r['codigo'], '1.1')) return false;
            foreach ($excluirActivo as $excl) {
                if (stripos($r['nombre'], $excl) !== false) return false;
            }
            return true;
        });

        foreach ($activoCorriente as $ac) {
            if ($ac['variacion'] == 0) continue;
            $actividades[] = [
                'concepto'  => $ac['nombre'],
                'monto'     => abs($ac['variacion']),
                'es_ajuste' => true,
                'tipo'      => $ac['variacion'] > 0 ? 'salida' : 'entrada',
            ];
        }

        // 5. Variaciones de pasivo corriente
        $pasivoCorriente = array_filter($origenAplicacion, function($r) {
            if ($r['tipo'] !== 'P') return false;
            if (!str_starts_with($r['codigo'], '2.1')) return false;
            return true;
        });

        foreach ($pasivoCorriente as $pc) {
            if ($pc['variacion'] == 0) continue;
            $actividades[] = [
                'concepto'  => $pc['nombre'],
                'monto'     => abs($pc['variacion']),
                'es_ajuste' => true,
                'tipo'      => $pc['variacion'] > 0 ? 'entrada' : 'salida',
            ];
        }

        return $actividades;
    }

    // ================================================================
    // ACTIVIDADES DE INVERSIÓN (activo no corriente 1.2)
    // ================================================================

    private function construirInversion(array $origenAplicacion, array $ajustesInflacion = []): array
    {
        $actividades = [];

        $noCorriente = array_filter($origenAplicacion, function($r) {
            if ($r['tipo'] !== 'A') return false;
            if (!str_starts_with($r['codigo'], '1.2')) return false;
            if (stripos($r['nombre'], 'DEPRECIA') !== false) return false;
            if (stripos($r['nombre'], 'AMORTIZA') !== false) return false;
            return true;
        });

        foreach ($noCorriente as $nc) {
            // FIX #3: descontamos la porción de la variación que corresponde
            // solo a revalorización por inflación (no es efectivo real)
            $variacionReal = $nc['variacion'] - ($ajustesInflacion[$nc['cuenta_id']] ?? 0);
            if ($variacionReal == 0) continue;

            $actividades[] = [
                'concepto' => $nc['nombre'],
                'monto'    => abs($variacionReal),
                'tipo'     => $variacionReal > 0 ? 'salida' : 'entrada',
                'es_ajuste'=> false,
            ];
        }

        return $actividades;
    }

    // ================================================================
    // ACTIVIDADES DE FINANCIAMIENTO (patrimonio 3.x y pasivo LP 2.2)
    // ================================================================

    private function construirFinanciamiento(array $origenAplicacion, array $ajustesInflacion = []): array
    {
        $actividades = [];

        $financiamiento = array_filter($origenAplicacion, function($r) {
            if ($r['tipo'] === 'PN') {
                if (stripos($r['nombre'], 'UTILIDAD') !== false) return false;
                if (stripos($r['nombre'], 'PÉRDIDA')  !== false) return false;
                if (stripos($r['nombre'], 'PERDIDA')  !== false) return false;
                return true;
            }
            if ($r['tipo'] === 'P' && str_starts_with($r['codigo'], '2.2')) return true;
            return false;
        });

        foreach ($financiamiento as $f) {
            // FIX #3: mismo criterio, descontar revalorización no monetaria
            $variacionReal = $f['variacion'] - ($ajustesInflacion[$f['cuenta_id']] ?? 0);
            if ($variacionReal == 0) continue;

            $actividades[] = [
                'concepto' => $f['nombre'],
                'monto'    => abs($variacionReal),
                'tipo'     => $variacionReal > 0 ? 'entrada' : 'salida',
                'es_ajuste'=> false,
            ];
        }

        return $actividades;
    }

    // ================================================================
    // HELPERS
    // ================================================================

    private function calcularResultadoGestion(int $periodoId): float
    {
        $ingresos = DetalleAsiento::select(DB::raw('SUM(haber - debe) as total'))
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->join('cuentas',  'detalle_asientos.cuenta_id',  '=', 'cuentas.id')
            ->where('asientos.periodo_id', $periodoId)
            ->where('cuentas.tipo', 'I')
            ->where('cuentas.permite_movimiento', true)
            ->first()->total ?? 0;

        $gastos = DetalleAsiento::select(DB::raw('SUM(debe - haber) as total'))
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->join('cuentas',  'detalle_asientos.cuenta_id',  '=', 'cuentas.id')
            ->where('asientos.periodo_id', $periodoId)
            ->where('cuentas.tipo', 'G')
            ->where('cuentas.permite_movimiento', true)
            ->first()->total ?? 0;

        return $ingresos - $gastos;
    }

    private function calcularEfectivo(array $saldos): float
    {
        $total = 0;
        foreach ($saldos as $s) {
            if ($s['tipo'] !== 'A') continue;
            if (stripos($s['nombre'], 'CAJA')    !== false ||
                stripos($s['nombre'], 'BANCO')   !== false ||
                stripos($s['nombre'], 'EFECTIVO')!== false) {
                $total += $s['saldo'];
            }
        }
        return $total;
    }

    private function sumarActividades(array $actividades): float
    {
        $total = 0;
        foreach ($actividades as $a) {
            $total += $a['tipo'] === 'entrada' ? $a['monto'] : -$a['monto'];
        }
        return $total;
    }
}
