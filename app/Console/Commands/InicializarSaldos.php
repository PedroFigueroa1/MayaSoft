<?php
// app/Console/Commands/InicializarSaldos.php

namespace App\Console\Commands;

use App\Models\Periodo;
use App\Services\SaldosPeriodoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InicializarSaldos extends Command
{
    protected $signature = 'contabilidad:inicializar-saldos
                            {--periodo= : ID del período específico}
                            {--truncate : Vaciar tabla antes de inicializar}';

    protected $description = 'Inicializa los saldos desde cero para todos los períodos';

    public function handle(SaldosPeriodoService $saldosService)
    {
        $this->info('=== INICIALIZACIÓN DE SALDOS ===');

        // Opción para vaciar tabla
        if ($this->option('truncate')) {
            if ($this->confirm('¿Está seguro de vaciar la tabla saldos_cuentas?')) {
                DB::table('saldos_cuentas')->truncate();
                $this->info('✅ Tabla vaciada');
            }
        }

        if ($this->option('periodo')) {
            $this->inicializarPeriodo($saldosService, $this->option('periodo'));
        } else {
            $this->inicializarTodosPeriodos($saldosService);
        }

        return Command::SUCCESS;
    }

    private function inicializarPeriodo(SaldosPeriodoService $saldosService, $periodoId)
    {
        $periodo = Periodo::find($periodoId);

        if (!$periodo) {
            $this->error("Período ID {$periodoId} no encontrado");
            return;
        }

        $this->info("Inicializando período: {$periodo->nombre}");

        // Mostrar resumen antes
        $resumen = $saldosService->getResumenPeriodo($periodoId);
        $this->table(
            ['Concepto', 'Valor'],
            [
                ['Total Débitos', number_format($resumen['total_debe'], 2)],
                ['Total Créditos', number_format($resumen['total_haber'], 2)],
                ['Diferencia', number_format($resumen['diferencia'], 2)],
                ['Cuentas con movimiento', $resumen['cuentas_con_movimiento']]
            ]
        );

        if (!$resumen['tiene_movimientos']) {
            $this->warn('Este período no tiene movimientos');
            if (!$this->confirm('¿Desea continuar de todos modos?')) {
                return;
            }
        }

        $this->line('Procesando...');

        $bar = $this->output->createProgressBar(1);
        $bar->start();

        $resultado = $saldosService->calcularSaldosPeriodo($periodoId, true);

        $bar->finish();
        $this->newLine(2);

        if ($resultado['success']) {
            $this->info('✅ ' . $resultado['message']);
            $this->line("Cuentas procesadas: {$resultado['total_cuentas']}");
            $this->line("Con movimiento: {$resultado['cuentas_con_movimiento']}");
            $this->line("Sin movimiento: {$resultado['cuentas_sin_movimiento']}");
        } else {
            $this->error('❌ ' . $resultado['message']);
        }
    }

    private function inicializarTodosPeriodos(SaldosPeriodoService $saldosService)
    {
        $periodos = Periodo::orderBy('fecha_inicio')->get();

        $this->info("Se inicializarán " . $periodos->count() . " períodos");

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
        $this->info('RESUMEN FINAL:');
        $tabla = [];

        foreach ($periodos as $periodo) {
            $res = $resultados[$periodo->id];
            $tabla[] = [
                $periodo->id,
                $periodo->nombre,
                $res['success'] ? '✅' : '❌',
                $res['cuentas_con_movimiento'] ?? 0,
                $res['cuentas_sin_movimiento'] ?? 0,
                count($res['errores'] ?? [])
            ];
        }

        $this->table(
            ['ID', 'Período', 'Estado', 'Ctas Mov', 'Ctas Sin Mov', 'Errores'],
            $tabla
        );
    }
}
