<?php
namespace App\Services;

use App\Models\Periodo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaldosPeriodoService
{
    /**
     * Calcular saldos en TIEMPO REAL directo desde cuentas y asientos.
     * YA NO usa tabla saldos_cuentas.
     */
    public function calcularSaldosPeriodo(int $periodoId, bool $soloConMovimiento = true): array
    {
        $periodo = Periodo::findOrFail($periodoId);

        Log::info('CÁLCULO DE SALDOS EN TIEMPO REAL', [
            'periodo_id' => $periodoId,
            'periodo'    => $periodo->nombre,
        ]);

        try {
            // 1. Subquery: movimientos del período agrupados por cuenta
            $movimientos = DB::table('detalle_asientos as da')
                ->join('asientos as a', 'a.id', '=', 'da.asiento_id')
                ->where('a.periodo_id', $periodoId)
                ->select(
                    'da.cuenta_id',
                    DB::raw('COALESCE(SUM(da.debe), 0)  as total_debe'),
                    DB::raw('COALESCE(SUM(da.haber), 0) as total_haber')
                )
                ->groupBy('da.cuenta_id');

            // 2. LEFT JOIN con TODAS las cuentas (cuentas no tiene empresa_id)
            $query = DB::table('cuentas as c')
                ->leftJoinSub($movimientos, 'mov', function ($join) {
                    $join->on('mov.cuenta_id', '=', 'c.id');
                })
                ->select(
                    'c.id as cuenta_id',
                    'c.codigo',
                    'c.nombre',
                    'c.tipo',
                    'c.permite_movimiento',
                    'c.nivel',
                    DB::raw('COALESCE(mov.total_debe, 0)  as total_debe'),
                    DB::raw('COALESCE(mov.total_haber, 0) as total_haber')
                );

            // 3. ¿Solo cuentas con movimiento?
            if ($soloConMovimiento) {
                $query->where(function ($q) {
                    $q->where('mov.total_debe', '>', 0)
                      ->orWhere('mov.total_haber', '>', 0);
                });
            }

            $cuentas = $query->orderBy('c.codigo')->get();

            if ($cuentas->isEmpty()) {
                return [
                    'success'                => true,
                    'message'                => 'No hay movimientos en este período',
                    'saldos'                 => [],
                    'errores'                => [],
                    'total_cuentas'          => 0,
                    'cuentas_con_movimiento' => 0,
                    'totales'                => ['debe' => 0, 'haber' => 0, 'saldo_debe' => 0, 'saldo_haber' => 0],
                ];
            }

            $saldosCalculados = [];
            $errores          = [];

            foreach ($cuentas as $cta) {
                try {
                    $debe  = floatval($cta->total_debe);
                    $haber = floatval($cta->total_haber);

                    $sfDebe  = 0;
                    $sfHaber = 0;

                    switch ($cta->tipo) {
                        case 'A': // ACTIVO
                        case 'G': // GASTO
                            $neto = $debe - $haber;
                            if ($neto > 0)      $sfDebe  = $neto;
                            elseif ($neto < 0)  $sfHaber = abs($neto);
                            break;

                        case 'P':  // PASIVO
                        case 'PN': // PATRIMONIO
                        case 'I':  // INGRESO
                            $neto = $haber - $debe;
                            if ($neto > 0)      $sfHaber = $neto;
                            elseif ($neto < 0)  $sfDebe  = abs($neto);
                            break;
                    }

                    $naturaleza = match(true) {
                        $sfDebe  > $sfHaber => 'DEUDORA',
                        $sfHaber > $sfDebe  => 'ACREEDORA',
                        default             => 'CERO',
                    };

                    $saldosCalculados[] = [
                        'cuenta_id'          => $cta->cuenta_id,
                        'codigo'             => $cta->codigo,
                        'nombre'             => $cta->nombre,
                        'tipo'               => $cta->tipo,
                        'permite_movimiento' => $cta->permite_movimiento,
                        'nivel'              => $cta->nivel,
                        'total_debe'         => $debe,
                        'total_haber'        => $haber,
                        'saldo_final_debe'   => $sfDebe,
                        'saldo_final_haber'  => $sfHaber,
                        'naturaleza'         => $naturaleza,
                    ];
                } catch (\Exception $e) {
                    $msg = "Error en cuenta {$cta->codigo}: " . $e->getMessage();
                    $errores[] = $msg;
                    Log::error($msg);
                }
            }

            $totales = [
                'debe'        => collect($saldosCalculados)->sum('total_debe'),
                'haber'       => collect($saldosCalculados)->sum('total_haber'),
                'saldo_debe'  => collect($saldosCalculados)->sum('saldo_final_debe'),
                'saldo_haber' => collect($saldosCalculados)->sum('saldo_final_haber'),
            ];

            return [
                'success'                => count($errores) === 0,
                'message'                => sprintf(
                    'Saldos en tiempo real: %d cuentas. Debe %.2f | Haber %.2f',
                    count($saldosCalculados), $totales['debe'], $totales['haber']
                ),
                'saldos'                 => $saldosCalculados,
                'errores'                => $errores,
                'total_cuentas'          => count($saldosCalculados),
                'cuentas_con_movimiento' => count($saldosCalculados),
                'totales'                => $totales,
            ];

        } catch (\Exception $e) {
            Log::error('ERROR CÁLCULO SALDOS TIEMPO REAL', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success'       => false,
                'message'       => 'Error calculando saldos: ' . $e->getMessage(),
                'saldos'        => [],
                'errores'       => [$e->getMessage()],
                'total_cuentas' => 0,
                'totales'       => ['debe' => 0, 'haber' => 0, 'saldo_debe' => 0, 'saldo_haber' => 0],
            ];
        }
    }

    /**
     * Resumen rápido de movimientos del período
     */
    public function getResumenPeriodo(int $periodoId): array
    {
        $totales = DB::table('detalle_asientos')
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->where('asientos.periodo_id', $periodoId)
            ->selectRaw('COALESCE(SUM(debe),0) as total_debe, COALESCE(SUM(haber),0) as total_haber')
            ->first();

        $cuentasConMovimiento = DB::table('detalle_asientos')
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->where('asientos.periodo_id', $periodoId)
            ->distinct('detalle_asientos.cuenta_id')
            ->count('detalle_asientos.cuenta_id');

        return [
            'cuentas_con_movimiento' => $cuentasConMovimiento,
            'total_debe'             => floatval($totales->total_debe  ?? 0),
            'total_haber'            => floatval($totales->total_haber ?? 0),
            'diferencia'             => floatval(($totales->total_debe ?? 0) - ($totales->total_haber ?? 0)),
        ];
    }
}
