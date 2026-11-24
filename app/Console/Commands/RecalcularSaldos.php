<?php
// app/Console/Commands/RecalcularSaldos.php

namespace App\Console\Commands;

use App\Models\Periodo;
use App\Services\SaldosPeriodoService;
use Illuminate\Console\Command;

class RecalcularSaldos extends Command
{
    protected $signature = 'contabilidad:recalcular-saldos
                            {--periodo= : ID del período específico}
                            {--todos : Recalcular todos los períodos}';

    protected $description = 'Recalcular saldos de cuentas con movimiento';

    public function handle(SaldosPeriodoService $saldosService)
    {
        $this->info('=== RECÁLCULO DE SALDOS (SOLO CUENTAS CON MOVIMIENTO) ===');

        if ($this->option('periodo')) {
            $this->recalcularPeriodo($saldosService, $this->option('periodo'));
        } elseif ($this->option('todos')) {
            $this->recalcularTodos($saldosService);
        } else {
            $this->error('Debe especificar --periodo o --todos');
        }
    }

    private function recalcularPeriodo(SaldosPeriodoService $saldosService, $periodoId)
    {
        $periodo = Periodo::find($periodoId);

        if (!$periodo) {
            $this->error("Período no encontrado");
            return;
        }

        $this->info("Período: {$periodo->nombre}");

        // Mostrar resumen antes del cálculo
        $resumen = $saldosService->getResumenPeriodo($periodoId);

        $this->table(
            ['Concepto', 'Valor'],
            [
                ['Cuentas con movimiento', $resumen['cuentas_con_movimiento']],
                ['Total Débitos', 'USD ' . number_format($resumen['total_debe'], 2)],
                ['Total Créditos', 'USD ' . number_format($resumen['total_haber'], 2)],
                ['Diferencia', 'USD ' . number_format($resumen['diferencia'], 2)]
            ]
        );

        if ($resumen['cuentas_con_movimiento'] == 0) {
            $this->warn('No hay movimientos en este período');
            return;
        }

        if (!$this->confirm('¿Desea recalcular los saldos?')) {
            return;
        }

        $this->line('Calculando...');

        $resultado = $saldosService->calcularSaldosPeriodo($periodoId, true);

        if ($resultado['success']) {
            $this->info('✅ ' . $resultado['message']);

            // Mostrar totales
            $this->table(
                ['Total Debe', 'Total Haber', 'Saldo Deudor', 'Saldo Acreedor'],
                [[
                    'USD ' . number_format($resultado['totales']['debe'], 2),
                    'USD ' . number_format($resultado['totales']['haber'], 2),
                    'USD ' . number_format($resultado['totales']['saldo_debe'], 2),
                    'USD ' . number_format($resultado['totales']['saldo_haber'], 2)
                ]]
            );

            // Mostrar primeras 10 cuentas como ejemplo
            if (!empty($resultado['saldos'])) {
                $this->line("\nPrimeras 10 cuentas calculadas:");
                $muestra = array_slice($resultado['saldos'], 0, 10);
                foreach ($muestra as $saldo) {
                    $this->line("  {$saldo['codigo']} - {$saldo['nombre']}: Debe {$saldo['debe']}, Haber {$saldo['haber']}");
                }
            }
        } else {
            $this->error('❌ ' . $resultado['message']);
            foreach ($resultado['errores'] as $error) {
                $this->line("  • {$error}");
            }
        }
    }

    private function recalcularTodos(SaldosPeriodoService $saldosService)
    {
        $periodos = Periodo::orderBy('fecha_inicio')->get();

        $this->info("Se procesarán " . $periodos->count() . " períodos");

        if (!$this->confirm('¿Desea continuar?')) {
            return;
        }

        $bar = $this->output->createProgressBar($periodos->count());
        $bar->start();

        $resultados = [];
        foreach ($periodos as $periodo) {
            $resultados[$periodo->id] = $saldosService->calcularSaldosPeriodo($periodo->id, true);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Mostrar resumen
        $this->info('RESUMEN POR PERÍODO:');
        foreach ($periodos as $periodo) {
            $res = $resultados[$periodo->id];
            $estado = $res['success'] ? '✅' : '❌';
            $this->line("{$estado} {$periodo->nombre}: {$res['total_cuentas']} cuentas");
        }
    }
}
