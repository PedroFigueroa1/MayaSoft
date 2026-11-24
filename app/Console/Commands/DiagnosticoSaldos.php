<?php
// app/Console/Commands/DiagnosticoSaldos.php

namespace App\Console\Commands;

use App\Models\Periodo;
use App\Models\Cuentas;
use App\Models\DetalleAsiento;
use App\Models\SaldoCuenta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnosticoSaldos extends Command
{
    protected $signature = 'contabilidad:diagnostico {--periodo= : ID del período a diagnosticar}';
    protected $description = 'Diagnostica problemas con el cálculo de saldos';

    public function handle()
    {
        $this->info('=== DIAGNÓSTICO DE SALDOS CONTABLES ===');
        $this->newLine();

        // 1. Verificar tabla saldos_cuentas
        $this->verificarTablaSaldos();

        // 2. Verificar períodos
        $periodoId = $this->option('periodo');
        if ($periodoId) {
            $this->diagnosticarPeriodo($periodoId);
        } else {
            $this->diagnosticarTodosPeriodos();
        }

        return Command::SUCCESS;
    }

    private function verificarTablaSaldos()
    {
        $this->info('1. VERIFICANDO TABLA SALDOS_CUENTAS');

        if (!DB::getSchemaBuilder()->hasTable('saldos_cuentas')) {
            $this->error('❌ La tabla saldos_cuentas NO existe');
            $this->warn('   Ejecuta: php artisan migrate');
            return;
        }

        $this->info('✅ Tabla saldos_cuentas existe');

        $count = SaldoCuenta::count();
        $this->line("   Registros actuales: {$count}");

        if ($count == 0) {
            $this->warn('   ⚠️ No hay registros en saldos_cuentas');
        }

        $this->newLine();
    }

    private function diagnosticarPeriodo($periodoId)
    {
        $periodo = Periodo::find($periodoId);

        if (!$periodo) {
            $this->error("❌ Período ID {$periodoId} no encontrado");
            return;
        }

        $this->info("2. DIAGNOSTICANDO PERÍODO: {$periodo->nombre}");
        $this->line("   ID: {$periodo->id}");
        $this->line("   Rango: {$periodo->fecha_inicio} al {$periodo->fecha_fin}");
        $this->newLine();

        // Verificar asientos en el período
        $this->verificarAsientos($periodoId);

        // Verificar detalles
        $this->verificarDetalles($periodoId);

        // Verificar cuentas
        $this->verificarCuentas($periodoId);

        // Verificar saldos calculados
        $this->verificarSaldosCalculados($periodoId);

        // Probar cálculo manual
        $this->probarCalculoManual($periodoId);
    }

    private function diagnosticarTodosPeriodos()
    {
        $periodos = Periodo::orderBy('fecha_inicio')->get();

        $this->info("2. DIAGNOSTICANDO TODOS LOS PERÍODOS (" . $periodos->count() . ")");
        $this->newLine();

        foreach ($periodos as $periodo) {
            $this->line("► Período: {$periodo->nombre}");

            $totalAsientos = DB::table('asientos')
                ->where('periodo_id', $periodo->id)
                ->count();

            $totalDetalles = DB::table('detalle_asientos')
                ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
                ->where('asientos.periodo_id', $periodo->id)
                ->count();

            $totalSaldos = SaldoCuenta::where('periodo_id', $periodo->id)->count();

            $this->line("   Asientos: {$totalAsientos}");
            $this->line("   Detalles: {$totalDetalles}");
            $this->line("   Saldos calculados: {$totalSaldos}");

            // Calcular totales debe/haber
            $totales = DB::table('detalle_asientos')
                ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
                ->where('asientos.periodo_id', $periodo->id)
                ->selectRaw('SUM(debe) as total_debe, SUM(haber) as total_haber')
                ->first();

            $this->line("   Total Debe: " . number_format($totales->total_debe ?? 0, 2));
            $this->line("   Total Haber: " . number_format($totales->total_haber ?? 0, 2));

            if ($totalSaldos == 0 && $totalDetalles > 0) {
                $this->error("   ⚠️ HAY DETALLES PERO NO SALDOS CALCULADOS");
            }

            $this->newLine();
        }
    }

    private function verificarAsientos($periodoId)
    {
        $totalAsientos = DB::table('asientos')
            ->where('periodo_id', $periodoId)
            ->count();

        $this->info("   Asientos en el período: {$totalAsientos}");

        if ($totalAsientos == 0) {
            $this->warn("   ⚠️ No hay asientos en este período");
        }

        // Verificar estados de asientos
        $estados = DB::table('asientos')
            ->where('periodo_id', $periodoId)
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->get();

        foreach ($estados as $estado) {
            $this->line("      {$estado->estado}: {$estado->total}");
        }

        $this->newLine();
    }

    private function verificarDetalles($periodoId)
    {
        $totalDetalles = DB::table('detalle_asientos')
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->where('asientos.periodo_id', $periodoId)
            ->count();

        $this->info("   Detalles de asientos: {$totalDetalles}");

        if ($totalDetalles > 0) {
            // Calcular totales
            $totales = DB::table('detalle_asientos')
                ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
                ->where('asientos.periodo_id', $periodoId)
                ->selectRaw('SUM(debe) as total_debe, SUM(haber) as total_haber')
                ->first();

            $this->line("      Total Debe: " . number_format($totales->total_debe ?? 0, 2));
            $this->line("      Total Haber: " . number_format($totales->total_haber ?? 0, 2));

            $diferencia = ($totales->total_debe ?? 0) - ($totales->total_haber ?? 0);
            if (abs($diferencia) > 0.01) {
                $this->error("      ⚠️ DIFERENCIA: " . number_format($diferencia, 2));
            } else {
                $this->info("      ✅ Balanceado");
            }
        }

        $this->newLine();
    }

    private function verificarCuentas($periodoId)
    {
        $totalCuentas = Cuentas::count();
        $this->info("   Total cuentas en catálogo: {$totalCuentas}");

        // Cuentas con movimientos
        $cuentasConMovimientos = DB::table('detalle_asientos')
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->where('asientos.periodo_id', $periodoId)
            ->distinct('detalle_asientos.cuenta_id')
            ->count('detalle_asientos.cuenta_id');

        $this->line("   Cuentas con movimientos: {$cuentasConMovimientos}");
        $this->newLine();
    }

    private function verificarSaldosCalculados($periodoId)
    {
        $saldos = SaldoCuenta::where('periodo_id', $periodoId)->count();

        $this->info("   Registros en saldos_cuentas: {$saldos}");

        if ($saldos > 0) {
            $totalDebe = SaldoCuenta::where('periodo_id', $periodoId)->sum('total_debe');
            $totalHaber = SaldoCuenta::where('periodo_id', $periodoId)->sum('total_haber');

            $this->line("      Total Debe (saldos): " . number_format($totalDebe, 2));
            $this->line("      Total Haber (saldos): " . number_format($totalHaber, 2));
        }

        $this->newLine();
    }

    private function probarCalculoManual($periodoId)
    {
        $this->info("   PRUEBA DE CÁLCULO MANUAL");

        // Tomar una cuenta de ejemplo
        $cuentaEjemplo = Cuentas::where('permite_movimiento', true)->first();

        if (!$cuentaEjemplo) {
            $this->warn("   No hay cuentas con movimiento para probar");
            return;
        }

        $this->line("   Cuenta de prueba: {$cuentaEjemplo->codigo} - {$cuentaEjemplo->nombre}");

        // Calcular movimientos directamente
        $movimientos = DB::table('detalle_asientos')
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->where('asientos.periodo_id', $periodoId)
            ->where('detalle_asientos.cuenta_id', $cuentaEjemplo->id)
            ->selectRaw('SUM(debe) as debe, SUM(haber) as haber')
            ->first();

        $this->line("      Debe calculado: " . number_format($movimientos->debe ?? 0, 2));
        $this->line("      Haber calculado: " . number_format($movimientos->haber ?? 0, 2));

        // Verificar si existe en saldos
        $saldoExistente = SaldoCuenta::where('periodo_id', $periodoId)
            ->where('cuenta_id', $cuentaEjemplo->id)
            ->first();

        if ($saldoExistente) {
            $this->line("      Saldo registrado: Debe=" . number_format($saldoExistente->total_debe, 2) .
                       ", Haber=" . number_format($saldoExistente->total_haber, 2));
        } else {
            $this->warn("      ⚠️ No hay registro en saldos_cuentas para esta cuenta");
        }

        $this->newLine();
    }
}
