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
use App\Exports\EvolucionPatrimonioExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class EvolucionPatrimonioController extends Controller
{
    public function generar(Periodo $periodo)
    {
        $data = $this->calcularEvolucion($periodo);

        return Inertia::render('EvolucionPatrimonio/Reporte', array_merge($data, [
            'periodo'             => $periodo,
            'empresa'             => $periodo->empresa,
            'fechaSaldoInicial'   => \Carbon\Carbon::parse($periodo->fecha_inicio)->subDay()->format('d/m/Y'), // ← agregar
        ]));
    }

    public function exportarExcel(Request $request)
    {
        $request->validate(['periodo_id' => 'required|exists:periodos,id']);

        $periodo = Periodo::findOrFail($request->periodo_id);
        $data    = $this->calcularEvolucion($periodo);

        $filename = (new EvolucionPatrimonioExport(
            saldoInicial: $data['saldoInicial'],
            movimientos:  $data['movimientos'],
            saldoFinal:   $data['saldoFinal'],
            periodo:      $periodo,
            empresa:      $periodo->empresa,
        ))->generate();

        $downloadUrl = URL::temporarySignedRoute(
            'evolucion-patrimonio.download-excel',
            now()->addMinutes(30),
            ['filename' => $filename]
        );

        return response()->json(['download_url' => $downloadUrl, 'filename' => $filename]);
    }

    public function downloadExcel(Request $request, $filename)
    {
        if (!$request->hasValidSignature()) abort(401);
        $path = storage_path('app/temp/' . $filename);
        if (!file_exists($path)) abort(404);
        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ])->deleteFileAfterSend(true);
    }

    // ================================================================
    // NÚCLEO
    // ================================================================

    private function calcularEvolucion(Periodo $periodo): array
    {
        // Saldo inicial = asiento de apertura del período (CT-001 / CIE)
        $saldoInicial = $this->obtenerSaldosDesdeApertura($periodo);

        // Movimientos del período sobre cuentas PN
        $movimientos = $this->calcularMovimientos($periodo, $saldoInicial);

        // Saldo final = saldo inicial + todos los movimientos del período
        $saldoFinal = $this->calcularSaldoFinal($periodo, $saldoInicial);

        return compact('saldoInicial', 'movimientos', 'saldoFinal');
    }

    // ================================================================
    // SALDO INICIAL desde asiento de apertura
    // ================================================================

    private function obtenerSaldosDesdeApertura(Periodo $periodo): array
    {
        // Buscar el asiento de apertura (primer asiento del período)
        $asientoApertura = Asiento::where('periodo_id', $periodo->id)
            ->where(function($q) {
                $q->where('tipo', 'CIE')
                  ->orWhere(function($q2) {
                      $q2->where('tipo', 'TRN')
                         ->where(function($q3) {
                             $q3->where('glosa',          'like', '%apertura%')
                                ->orWhere('glosa',         'like', '%Apertura%')
                                ->orWhere('numero_asiento','like', 'CT-001%')
                                ->orWhere('numero_asiento','like', 'CI-001%');
                         });
                  });
            })
            ->orderBy('id')
            ->first();

        if (!$asientoApertura) {
            // Si no hay asiento de apertura, intentar con el primer asiento del período
            $asientoApertura = Asiento::where('periodo_id', $periodo->id)
                ->orderBy('id')
                ->first();
        }

        $vacío = $this->estructuraVacia();

        if (!$asientoApertura) return $vacío;

        // Leer los detalles del asiento de apertura
        $detalles = DetalleAsiento::where('asiento_id', $asientoApertura->id)
            ->with('cuenta')
            ->get();

        $result = $vacío;

        foreach ($detalles as $d) {
            if (!$d->cuenta || $d->cuenta->tipo !== 'PN') continue;

            // Saldo de la cuenta PN = haber - debe (naturaleza acreedora)
            $monto = $d->haber - $d->debe;
            $nombre = strtoupper($d->cuenta->nombre);

            if ($this->esCapitalSocial($nombre)) {
                $result['capital_social'] += $monto;
            } elseif ($this->esAjusteCapital($nombre)) {
                $result['ajuste_capital'] += $monto;
            } elseif ($this->esUtilidad($nombre)) {
                $result['utilidad_gestion'] += $monto;
            } elseif ($this->esResultadosAcumulados($nombre)) {
                $result['resultados_acumulados'] += $monto;
            } elseif ($this->esAjusteGlobal($nombre)) {
                $result['ajuste_global'] += $monto;
            } elseif ($this->esReservaLegal($nombre)) {
                $result['reserva_legal'] += $monto;
            }
        }

        $result['total'] = $this->calcularTotal($result);
        return $result;
    }

    // ================================================================
    // MOVIMIENTOS DEL PERÍODO
    // ================================================================

    private function calcularMovimientos(Periodo $periodo, array $saldoInicial): array
    {
        $movimientos = [];

        // 1. Traspaso resultados → acumulados
        //    (asiento que debita Utilidad de Gestión y acredita Resultados Acumulados)
        $traspaso = $this->buscarTraspasoResultados($periodo);
        if ($traspaso['utilidad_gestion'] != 0 || $traspaso['resultados_acumulados'] != 0) {
            $movimientos[] = array_merge(
                ['descripcion' => 'Traspaso de Resultados a Acumulados'],
                $traspaso
            );
        }

        // 2. Actualización por inflación / mantenimiento de valor (afecta Ajuste Capital y Resultados Acumulados)
        $actualizacion = $this->buscarActualizacionCapital($periodo);
        if ($actualizacion['total'] != 0) {
            $movimientos[] = array_merge(
                ['descripcion' => 'Actualización cuentas de capital'],
                $actualizacion
            );
        }

        // 3. Utilidad de la gestión actual
        $utilidad = $this->calcularUtilidadGestion($periodo->id);
        if ($utilidad != 0) {
            $mov = $this->estructuraVacia();
            $mov['utilidad_gestion'] = $utilidad;
            $mov['total']            = $utilidad;
            $movimientos[] = array_merge(['descripcion' => 'Utilidad Gestión Actual'], $mov);
        }

        // 4. Aportes de capital (si hubo)
        $aporte = $this->buscarAportesCapital($periodo);
        if ($aporte['capital_social'] != 0) {
            $movimientos[] = array_merge(['descripcion' => 'Aportes de capital'], $aporte);
        }

        return $movimientos;
    }

    // ================================================================
    // SALDO FINAL
    // ================================================================

    private function calcularSaldoFinal(Periodo $periodo, array $saldoInicial): array
    {
        // ✅ Buscar el id del asiento de apertura para excluirlo
        $asientoApertura = Asiento::where('periodo_id', $periodo->id)
            ->where(function($q) {
                $q->where('tipo', 'CIE')
                ->orWhere(function($q2) {
                    $q2->where('tipo', 'TRN')
                        ->where(function($q3) {
                            $q3->where('glosa',          'like', '%apertura%')
                                ->orWhere('glosa',         'like', '%Apertura%')
                                ->orWhere('numero_asiento','like', 'CT-001%')
                                ->orWhere('numero_asiento','like', 'CI-001%');
                        });
                });
            })
            ->orderBy('id')
            ->first();

        // ✅ Obtener movimientos del período EXCLUYENDO el asiento de apertura
        $query = DetalleAsiento::select(
                'cuentas.nombre',
                DB::raw('SUM(detalle_asientos.haber - detalle_asientos.debe) as saldo')
            )
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->join('cuentas',  'detalle_asientos.cuenta_id',  '=', 'cuentas.id')
            ->where('asientos.periodo_id', $periodo->id)
            ->where('cuentas.tipo', 'PN')
            ->where('cuentas.permite_movimiento', true);

        // ✅ Excluir el asiento de apertura si existe
        if ($asientoApertura) {
            $query->where('detalle_asientos.asiento_id', '!=', $asientoApertura->id);
        }

        $resultado = $query->groupBy('cuentas.id', 'cuentas.nombre')->get();

        $movPN = $this->estructuraVacia();
        foreach ($resultado as $r) {
            $nombre = strtoupper($r->nombre);
            if ($this->esCapitalSocial($nombre))            $movPN['capital_social']        += $r->saldo;
            elseif ($this->esAjusteCapital($nombre))        $movPN['ajuste_capital']        += $r->saldo;
            elseif ($this->esUtilidad($nombre))             $movPN['utilidad_gestion']      += $r->saldo;
            elseif ($this->esResultadosAcumulados($nombre)) $movPN['resultados_acumulados'] += $r->saldo;
            elseif ($this->esAjusteGlobal($nombre))         $movPN['ajuste_global']         += $r->saldo;
            elseif ($this->esReservaLegal($nombre))         $movPN['reserva_legal']         += $r->saldo;
        }

        // ✅ Utilidad de I/G (cuentas tipo I y G, nunca en PN)
        $utilidad = $this->calcularUtilidadGestion($periodo->id);

        $final = [
            'capital_social'        => $saldoInicial['capital_social']        + $movPN['capital_social'],
            'ajuste_capital'        => $saldoInicial['ajuste_capital']        + $movPN['ajuste_capital'],
            'utilidad_gestion'      => $utilidad,
            'resultados_acumulados' => $saldoInicial['resultados_acumulados'] + $movPN['resultados_acumulados'],
            'ajuste_global'         => $saldoInicial['ajuste_global']         + $movPN['ajuste_global'],
            'reserva_legal'         => $saldoInicial['reserva_legal']         + $movPN['reserva_legal'],
        ];

        $final['total'] = $this->calcularTotal($final);
        return $final;
    }

    // ================================================================
    // BÚSQUEDA DE MOVIMIENTOS ESPECÍFICOS
    // ================================================================

    private function buscarTraspasoResultados(Periodo $periodo): array
    {
        $mov = $this->estructuraVacia();

        // Buscar asientos de traspaso dentro del período
        $asientos = Asiento::where('periodo_id', $periodo->id)
            ->where(function($q) {
                $q->where('tipo', 'TRN')
                  ->where(function($q2) {
                      $q2->where('glosa', 'like', '%traspaso%')
                         ->orWhere('glosa', 'like', '%Traspaso%')
                         ->orWhere('glosa', 'like', '%resultado%');
                  });
            })
            ->get();

        foreach ($asientos as $asiento) {
            $detalles = DetalleAsiento::where('asiento_id', $asiento->id)
                ->with('cuenta')
                ->get();

            foreach ($detalles as $d) {
                if (!$d->cuenta || $d->cuenta->tipo !== 'PN') continue;
                $nombre = strtoupper($d->cuenta->nombre);
                $monto  = $d->haber - $d->debe;

                if ($this->esUtilidad($nombre))              $mov['utilidad_gestion']       += $monto;
                elseif ($this->esResultadosAcumulados($nombre)) $mov['resultados_acumulados'] += $monto;
            }
        }

        $mov['total'] = $mov['utilidad_gestion'] + $mov['resultados_acumulados'];
        return $mov;
    }

    private function buscarActualizacionCapital(Periodo $periodo): array
    {
        $mov = $this->estructuraVacia();

        // Buscar ajustes que afectan cuentas PN (ajuste inflación → ajuste capital / resultados acumulados)
        $asientos = Asiento::where('periodo_id', $periodo->id)
            ->where('tipo', 'AJT')
            ->get();

        foreach ($asientos as $asiento) {
            $detalles = DetalleAsiento::where('asiento_id', $asiento->id)
                ->with('cuenta')
                ->get();

            foreach ($detalles as $d) {
                if (!$d->cuenta || $d->cuenta->tipo !== 'PN') continue;
                $nombre = strtoupper($d->cuenta->nombre);
                $monto  = $d->haber - $d->debe;

                if ($this->esAjusteCapital($nombre))           $mov['ajuste_capital']         += $monto;
                elseif ($this->esResultadosAcumulados($nombre)) $mov['resultados_acumulados'] += $monto;
                elseif ($this->esAjusteGlobal($nombre))        $mov['ajuste_global']          += $monto;
            }
        }

        $mov['total'] = $mov['ajuste_capital'] + $mov['resultados_acumulados'] + $mov['ajuste_global'];
        return $mov;
    }

    private function buscarAportesCapital(Periodo $periodo): array
    {
        $mov = $this->estructuraVacia();

        $resultado = DetalleAsiento::select(
                DB::raw('SUM(detalle_asientos.haber - detalle_asientos.debe) as saldo')
            )
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->join('cuentas',  'detalle_asientos.cuenta_id',  '=', 'cuentas.id')
            ->where('asientos.periodo_id', $periodo->id)
            ->where('cuentas.tipo', 'PN')
            ->where(function($q) {
                $q->where('cuentas.nombre', 'like', '%Capital Social%')
                  ->orWhere('cuentas.nombre', 'like', '%CAPITAL SOCIAL%');
            })
            ->where('cuentas.permite_movimiento', true)
            // Excluir asientos de apertura
            ->whereHas('asiento', function($q) use ($periodo) {
                $q->where('periodo_id', $periodo->id)
                  ->whereNotIn('numero_asiento', ['CT-001', 'CI-001', 'CIE-001']);
            })
            ->first();

        $mov['capital_social'] = $resultado->saldo ?? 0;
        $mov['total']          = $mov['capital_social'];
        return $mov;
    }

    private function calcularUtilidadGestion(int $periodoId): float
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

    // ================================================================
    // HELPERS DE CLASIFICACIÓN
    // ================================================================

    private function estructuraVacia(): array
    {
        return [
            'capital_social'        => 0,
            'ajuste_capital'        => 0,
            'utilidad_gestion'      => 0,
            'resultados_acumulados' => 0,
            'ajuste_global'         => 0,
            'reserva_legal'         => 0,
            'total'                 => 0,
        ];
    }

    private function calcularTotal(array $s): float
    {
        return $s['capital_social'] + $s['ajuste_capital'] + $s['utilidad_gestion']
             + $s['resultados_acumulados'] + $s['ajuste_global'] + $s['reserva_legal'];
    }

    private function esCapitalSocial(string $n): bool
    {
        return str_contains($n, 'CAPITAL SOCIAL') || str_contains($n, 'CAPITAL PAGADO');
    }

    private function esAjusteCapital(string $n): bool
    {
        return str_contains($n, 'AJUSTE DE CAPITAL') || str_contains($n, 'AJUSTE CAPITAL');
    }

    private function esUtilidad(string $n): bool
    {
        return str_contains($n, 'UTILIDAD DE LA GESTIÓN') || str_contains($n, 'UTILIDAD DE LA GESTION')
            || str_contains($n, 'PÉRDIDA DE LA GESTIÓN')  || str_contains($n, 'PERDIDA DE LA GESTION')
            || (str_contains($n, 'UTILIDAD') && str_contains($n, 'GESTIÓN'))
            || (str_contains($n, 'UTILIDAD') && str_contains($n, 'GESTION'));
    }

    private function esResultadosAcumulados(string $n): bool
    {
        return str_contains($n, 'RESULTADO') && str_contains($n, 'ACUMUL');
    }

    private function esAjusteGlobal(string $n): bool
    {
        return str_contains($n, 'AJUSTE GLOBAL') || str_contains($n, 'AJUSTE PATRIMONIAL');
    }

    private function esReservaLegal(string $n): bool
    {
        return str_contains($n, 'RESERVA LEGAL');
    }
}
