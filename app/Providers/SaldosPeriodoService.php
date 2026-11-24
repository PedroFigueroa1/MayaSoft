<?php
// app/Services/SaldosPeriodoService.php

namespace App\Services;

use App\Models\Periodo;
use App\Models\SaldoCuenta;
use App\Models\Cuentas;
use App\Models\DetalleAsiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaldosPeriodoService
{
    /**
     * Calcular y guardar saldos para un período
     */
    public function calcularSaldosPeriodo(int $periodoId, bool $forzarRecalculo = false): array
    {
        $periodo = Periodo::findOrFail($periodoId);

        // Verificar si ya existen saldos calculados y no se fuerza recálculo
        if (!$forzarRecalculo && $this->existenSaldosCalculados($periodoId)) {
            return ['message' => 'Los saldos ya están calculados', 'saldos' => SaldoCuenta::where('periodo_id', $periodoId)->get()];
        }

        DB::beginTransaction();

        try {
            // Obtener período anterior para saldos iniciales
            $periodoAnterior = $this->obtenerPeriodoAnterior($periodo);

            // Obtener todas las cuentas activas
            $cuentas = Cuentas::where('activo', true)->orderBy('codigo')->get();

            $saldosCalculados = [];

            foreach ($cuentas as $cuenta) {
                // Obtener saldos iniciales del período anterior
                $saldosIniciales = $this->obtenerSaldosIniciales($cuenta->id, $periodoAnterior);

                // Obtener movimientos del período actual
                $movimientos = $this->obtenerMovimientosPeriodo($cuenta->id, $periodoId);

                // Calcular saldos finales
                $saldosFinales = $this->calcularSaldosFinales(
                    $cuenta->tipo,
                    $saldosIniciales,
                    $movimientos
                );

                // Guardar o actualizar saldo de la cuenta
                $saldoCuenta = SaldoCuenta::updateOrCreate(
                    [
                        'periodo_id' => $periodoId,
                        'cuenta_id' => $cuenta->id
                    ],
                    [
                        'saldo_inicial_deudor' => $saldosIniciales['deudor'],
                        'saldo_inicial_acreedor' => $saldosIniciales['acreedor'],
                        'total_debe' => $movimientos['debe'],
                        'total_haber' => $movimientos['haber'],
                        'saldo_final_deudor' => $saldosFinales['deudor'],
                        'saldo_final_acreedor' => $saldosFinales['acreedor'],
                        'estado' => 'RECALCULADO',
                        'ultimo_calculo' => now(),
                        'observaciones' => $forzarRecalculo ? 'Recalculado manualmente' : null
                    ]
                );

                $saldosCalculados[] = $saldoCuenta;
            }

            // Marcar período como procesado
            DB::commit();

            return [
                'success' => true,
                'message' => 'Saldos calculados exitosamente',
                'saldos' => $saldosCalculados
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error calculando saldos: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error calculando saldos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verificar si existen saldos calculados
     */
    private function existenSaldosCalculados(int $periodoId): bool
    {
        return SaldoCuenta::where('periodo_id', $periodoId)->exists();
    }

    /**
     * Obtener período anterior
     */
    private function obtenerPeriodoAnterior(Periodo $periodoActual): ?Periodo
    {
        return Periodo::where('fecha_fin', '<', $periodoActual->fecha_inicio)
            ->orderBy('fecha_fin', 'desc')
            ->first();
    }

    /**
     * Obtener saldos iniciales desde período anterior
     */
    private function obtenerSaldosIniciales(int $cuentaId, ?Periodo $periodoAnterior): array
    {
        if (!$periodoAnterior) {
            return ['deudor' => 0, 'acreedor' => 0];
        }

        $saldoAnterior = SaldoCuenta::where('periodo_id', $periodoAnterior->id)
            ->where('cuenta_id', $cuentaId)
            ->first();

        if (!$saldoAnterior) {
            return ['deudor' => 0, 'acreedor' => 0];
        }

        return [
            'deudor' => $saldoAnterior->saldo_final_deudor,
            'acreedor' => $saldoAnterior->saldo_final_acreedor
        ];
    }

    /**
     * Obtener movimientos del período
     */
    private function obtenerMovimientosPeriodo(int $cuentaId, int $periodoId): array
    {
        $movimientos = DetalleAsiento::whereHas('asiento', function ($query) use ($periodoId) {
                $query->where('periodo_id', $periodoId)
                      ->where('estado', 'APROBADO');
            })
            ->where('cuenta_id', $cuentaId)
            ->selectRaw('COALESCE(SUM(debe), 0) as total_debe, COALESCE(SUM(haber), 0) as total_haber')
            ->first();

        return [
            'debe' => $movimientos->total_debe ?? 0,
            'haber' => $movimientos->total_haber ?? 0
        ];
    }

    /**
     * Calcular saldos finales según tipo de cuenta
     */
    private function calcularSaldosFinales(string $tipoCuenta, array $saldosIniciales, array $movimientos): array
    {
        $deudor = 0;
        $acreedor = 0;

        switch ($tipoCuenta) {
            case 'ACTIVO':
            case 'GASTO':
                // Cuentas de naturaleza deudora
                $deudor = $saldosIniciales['deudor'] + $movimientos['debe'] - $movimientos['haber'];
                $acreedor = 0;
                if ($deudor < 0) {
                    // Si hay saldo acreedor, lo movemos a la columna correspondiente
                    $acreedor = abs($deudor);
                    $deudor = 0;
                }
                break;

            case 'PASIVO':
            case 'PATRIMONIO':
            case 'INGRESO':
                // Cuentas de naturaleza acreedora
                $acreedor = $saldosIniciales['acreedor'] + $movimientos['haber'] - $movimientos['debe'];
                $deudor = 0;
                if ($acreedor < 0) {
                    // Si hay saldo deudor, lo movemos a la columna correspondiente
                    $deudor = abs($acreedor);
                    $acreedor = 0;
                }
                break;

            default:
                // Cuentas de resultados o mixtas
                $neto = ($saldosIniciales['deudor'] - $saldosIniciales['acreedor']) +
                        ($movimientos['debe'] - $movimientos['haber']);

                if ($neto > 0) {
                    $deudor = $neto;
                } else {
                    $acreedor = abs($neto);
                }
                break;
        }

        return [
            'deudor' => round($deudor, 2),
            'acreedor' => round($acreedor, 2)
        ];
    }
}
