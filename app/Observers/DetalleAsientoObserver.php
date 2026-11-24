<?php
// app/Observers/DetalleAsientoObserver.php

namespace App\Observers;

use App\Models\DetalleAsiento;
use App\Models\SaldoCuenta;
use App\Models\Periodo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DetalleAsientoObserver
{
    /**
     * Handle the DetalleAsiento "created" event.
     */
    public function created(DetalleAsiento $detalle): void
    {
        $this->actualizarSaldoEnTiempoReal($detalle, 'creado');
    }

    /**
     * Handle the DetalleAsiento "updated" event.
     */
    public function updated(DetalleAsiento $detalle): void
    {
        // Obtener valores originales
        $originalDebe = $detalle->getOriginal('debe');
        $originalHaber = $detalle->getOriginal('haber');

        // Si los montos cambiaron, ajustar
        if ($originalDebe != $detalle->debe || $originalHaber != $detalle->haber) {
            // Primero revertir el efecto original
            $this->revertirEfectoSaldo(
                $detalle->cuenta_id,
                $detalle->asiento->periodo_id,
                $originalDebe,
                $originalHaber
            );

            // Luego aplicar el nuevo efecto
            $this->aplicarEfectoSaldo($detalle, 'actualizado');
        }
    }

    /**
     * Handle the DetalleAsiento "deleted" event.
     */
    public function deleted(DetalleAsiento $detalle): void
    {
        $this->revertirEfectoSaldo(
            $detalle->cuenta_id,
            $detalle->asiento->periodo_id,
            $detalle->debe,
            $detalle->haber
        );
    }

    /**
     * Actualizar saldo en tiempo real
     */
    private function actualizarSaldoEnTiempoReal(DetalleAsiento $detalle, string $accion): void
    {
        try {
            DB::beginTransaction();

            $periodoId = $detalle->asiento->periodo_id;
            $cuentaId = $detalle->cuenta_id;

            // Obtener o crear registro de saldo
            $saldoCuenta = $this->obtenerOCrearSaldoCuenta($periodoId, $cuentaId);

            // Actualizar movimientos
            $saldoCuenta->total_debe += $detalle->debe;
            $saldoCuenta->total_haber += $detalle->haber;

            // Recalcular saldo final
            $this->recalcularSaldoFinal($saldoCuenta);

            $saldoCuenta->ultimo_calculo = now();
            $saldoCuenta->estado = 'ACTIVO';
            $saldoCuenta->save();

            DB::commit();

            Log::info("Saldo actualizado en tiempo real", [
                'cuenta_id' => $cuentaId,
                'periodo_id' => $periodoId,
                'accion' => $accion
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error actualizando saldo en tiempo real: " . $e->getMessage(), [
                'detalle_id' => $detalle->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Aplicar efecto de un detalle
     */
    private function aplicarEfectoSaldo(DetalleAsiento $detalle, string $accion): void
    {
        $this->actualizarSaldoEnTiempoReal($detalle, $accion);
    }

    /**
     * Revertir efecto de un detalle
     */
    private function revertirEfectoSaldo(int $cuentaId, int $periodoId, float $debe, float $haber): void
    {
        try {
            DB::beginTransaction();

            $saldoCuenta = SaldoCuenta::where('periodo_id', $periodoId)
                ->where('cuenta_id', $cuentaId)
                ->first();

            if ($saldoCuenta) {
                $saldoCuenta->total_debe -= $debe;
                $saldoCuenta->total_haber -= $haber;

                // Asegurar que no queden negativos (por si acaso)
                $saldoCuenta->total_debe = max(0, $saldoCuenta->total_debe);
                $saldoCuenta->total_haber = max(0, $saldoCuenta->total_haber);

                $this->recalcularSaldoFinal($saldoCuenta);
                $saldoCuenta->save();
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error revirtiendo efecto de saldo: " . $e->getMessage());
        }
    }

    /**
     * Obtener o crear registro de saldo cuenta
     */
    private function obtenerOCrearSaldoCuenta(int $periodoId, int $cuentaId): SaldoCuenta
    {
        $saldoCuenta = SaldoCuenta::firstOrNew([
            'periodo_id' => $periodoId,
            'cuenta_id' => $cuentaId
        ]);

        if (!$saldoCuenta->exists) {
            // Obtener período anterior para saldos iniciales
            $periodoActual = Periodo::find($periodoId);
            $periodoAnterior = Periodo::where('fecha_fin', '<', $periodoActual->fecha_inicio)
                ->orderBy('fecha_fin', 'desc')
                ->first();

            if ($periodoAnterior) {
                $saldoAnterior = SaldoCuenta::where('periodo_id', $periodoAnterior->id)
                    ->where('cuenta_id', $cuentaId)
                    ->first();

                if ($saldoAnterior) {
                    $saldoCuenta->saldo_inicial_debe = $saldoAnterior->saldo_final_debe;
                    $saldoCuenta->saldo_inicial_haber = $saldoAnterior->saldo_final_haber;
                }
            }

            $saldoCuenta->total_debe = 0;
            $saldoCuenta->total_haber = 0;
            $saldoCuenta->saldo_final_debe = $saldoCuenta->saldo_inicial_debe;
            $saldoCuenta->saldo_final_haber = $saldoCuenta->saldo_inicial_haber;
        }

        return $saldoCuenta;
    }

    /**
     * Recalcular saldo final según tipo de cuenta
     */
    private function recalcularSaldoFinal(SaldoCuenta $saldoCuenta): void
    {
        $cuenta = $saldoCuenta->cuenta;

        switch ($cuenta->tipo) {
            case 'A': // ACTIVO
            case 'G': // GASTO
                $neto = $saldoCuenta->saldo_inicial_debe +
                        $saldoCuenta->total_debe -
                        $saldoCuenta->total_haber;

                if ($neto >= 0) {
                    $saldoCuenta->saldo_final_debe = $neto;
                    $saldoCuenta->saldo_final_haber = 0;
                } else {
                    $saldoCuenta->saldo_final_debe = 0;
                    $saldoCuenta->saldo_final_haber = abs($neto);
                }
                break;

            case 'P': // PASIVO
            case 'PN': // PATRIMONIO
            case 'I': // INGRESO
                $neto = $saldoCuenta->saldo_inicial_haber +
                        $saldoCuenta->total_haber -
                        $saldoCuenta->total_debe;

                if ($neto >= 0) {
                    $saldoCuenta->saldo_final_haber = $neto;
                    $saldoCuenta->saldo_final_debe = 0;
                } else {
                    $saldoCuenta->saldo_final_haber = 0;
                    $saldoCuenta->saldo_final_debe = abs($neto);
                }
                break;
        }
    }
}
