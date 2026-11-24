<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Periodo;
use App\Models\DetalleAsiento;
use App\Models\Cuenta; // ← NUEVO
use App\Services\SaldosPeriodoService;
use App\Exports\SumasSaldosExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class SumasSaldosController extends Controller
{
    protected $saldosService;

    public function __construct(SaldosPeriodoService $saldosService)
    {
        $this->saldosService = $saldosService;
    }

    public function index(Request $request, $periodoId)
    {
        $periodo = Periodo::findOrFail($periodoId);
        $empresa = Empresa::find($periodo->empresa_id);

        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        // ─────────────────────────────────────────────
        // Si hay fechas → calcular manualmente filtrado
        // Si no hay fechas → usar el servicio (todo el período)
        // ─────────────────────────────────────────────
        if ($desde && $hasta) {
            $saldosBrutos = $this->calcularSaldosPorFechas($periodoId, $desde, $hasta);
            $balance      = $this->calcularBalancePorFechas($periodoId, $desde, $hasta);
        } else {
            $resultado    = $this->saldosService->calcularSaldosPeriodo($periodoId, true);
            $saldosBrutos = collect($resultado['saldos'] ?? []);
            $balance      = $this->calcularBalance($periodoId);
        }

        $sumasSaldos = $saldosBrutos->map(function ($item) {
            return [
                'id'                  => $item['cuenta_id'],
                'cuenta_id'           => $item['cuenta_id'],
                'codigo'              => $item['codigo'],
                'nombre'              => $item['nombre'],
                'tipo'                => $this->getTipoTexto($item['tipo']),
                'tipo_codigo'         => $item['tipo'],
                'saldo_inicial_debe'  => $item['saldo_inicial_debe']  ?? 0,
                'saldo_inicial_haber' => $item['saldo_inicial_haber'] ?? 0,
                'total_debe'          => $item['total_debe'],
                'total_haber'         => $item['total_haber'],
                'saldo_final_debe'    => $item['saldo_final_debe'],
                'saldo_final_haber'   => $item['saldo_final_haber'],
                'naturaleza'          => $item['naturaleza'],
                'permite_movimiento'  => $item['permite_movimiento'],
                'nivel'               => $item['nivel'],
            ];
        });

        $totales = [
            'total_debe'        => $sumasSaldos->sum('total_debe'),
            'total_haber'       => $sumasSaldos->sum('total_haber'),
            'saldo_final_debe'  => $sumasSaldos->sum('saldo_final_debe'),
            'saldo_final_haber' => $sumasSaldos->sum('saldo_final_haber'),
        ];

        return Inertia::render('SumasSaldos/Index', [
            'periodo'          => $periodo,
            'empresa'          => $empresa,
            'sumasSaldos'      => $sumasSaldos,
            'totales'          => $totales,
            'balance'          => $balance,
            'fecha_calculo'    => now()->format('Y-m-d H:i:s'),
            'puede_recalcular' => true,
            'desde'            => $desde,
            'hasta'            => $hasta,
        ]);
    }

    public function recalcular(Request $request, $periodoId)
    {
        return redirect()
            ->route('sumas-saldos.index', $periodoId)
            ->with('success', 'Saldos recalculados en tiempo real.');
    }

    public function exportarExcel(Request $request, $periodoId)
    {
        $periodo = Periodo::findOrFail($periodoId);
        $export  = new SumasSaldosExport($periodoId, $request->input('desde'), $request->input('hasta'));
        $nombre  = "sumas_saldos_{$periodo->nombre}_{$periodo->fecha_inicio}_{$periodo->fecha_cierre}.xlsx";

        return Excel::download($export, $nombre);
    }

    public function generarReporte(Request $request, $periodoId)
    {
        $periodo = Periodo::findOrFail($periodoId);
        $desde   = $request->input('desde');
        $hasta   = $request->input('hasta');

        if ($desde && $hasta) {
            $saldos = $this->calcularSaldosPorFechas($periodoId, $desde, $hasta)->map(function ($item) {
                return [
                    'id'                  => $item['cuenta_id'],
                    'cuenta_id'           => $item['cuenta_id'],
                    'codigo'              => $item['codigo'],
                    'nombre'              => $item['nombre'],
                    'tipo'                => $this->getTipoTexto($item['tipo']),
                    'tipo_codigo'         => $item['tipo'],
                    'saldo_inicial_debe'  => $item['saldo_inicial_debe']  ?? 0,
                    'saldo_inicial_haber' => $item['saldo_inicial_haber'] ?? 0,
                    'saldo_inicial'       => ($item['saldo_inicial_debe'] ?? 0) - ($item['saldo_inicial_haber'] ?? 0),
                    'total_debe'          => $item['total_debe'],
                    'total_haber'         => $item['total_haber'],
                    'saldo_final_debe'    => $item['saldo_final_debe'],
                    'saldo_final_haber'   => $item['saldo_final_haber'],
                    'saldo_final'         => $item['saldo_final_debe'] - $item['saldo_final_haber'],
                    'naturaleza'          => $item['naturaleza'],
                    'permite_movimiento'  => $item['permite_movimiento'],
                    'nivel'               => $item['nivel'],
                ];
            });
        } else {
            $resultado = $this->saldosService->calcularSaldosPeriodo($periodoId, true);
            $saldos = collect($resultado['saldos'] ?? [])->map(function ($item) {
                return [
                    'id'                  => $item['cuenta_id'],
                    'cuenta_id'           => $item['cuenta_id'],
                    'codigo'              => $item['codigo'],
                    'nombre'              => $item['nombre'],
                    'tipo'                => $this->getTipoTexto($item['tipo']),
                    'tipo_codigo'         => $item['tipo'],
                    'saldo_inicial_debe'  => 0,
                    'saldo_inicial_haber' => 0,
                    'saldo_inicial'       => 0,
                    'total_debe'          => $item['total_debe'],
                    'total_haber'         => $item['total_haber'],
                    'saldo_final_debe'    => $item['saldo_final_debe'],
                    'saldo_final_haber'   => $item['saldo_final_haber'],
                    'saldo_final'         => $item['saldo_final_debe'] - $item['saldo_final_haber'],
                    'naturaleza'          => $item['naturaleza'],
                    'permite_movimiento'  => $item['permite_movimiento'],
                    'nivel'               => $item['nivel'],
                ];
            });
        }

        return Inertia::render('SumasSaldos/Report', [
            'periodo'       => $periodo,
            'sumasSaldos'   => $saldos,
            'fecha_reporte' => now()->format('Y-m-d H:i:s'),
            'desde'         => $desde,
            'hasta'         => $hasta,
        ]);
    }

    private function getTipoTexto($codigo): string
    {
        return match ($codigo) {
            'A'  => 'ACTIVO',
            'P'  => 'PASIVO',
            'PN' => 'PATRIMONIO',
            'I'  => 'INGRESO',
            'G'  => 'GASTO',
            default => 'DESCONOCIDO',
        };
    }

    private function calcularBalance($periodoId): array
    {
        $debe  = DetalleAsiento::whereHas('asiento', fn ($q) => $q->where('periodo_id', $periodoId))->sum('debe');
        $haber = DetalleAsiento::whereHas('asiento', fn ($q) => $q->where('periodo_id', $periodoId))->sum('haber');

        return [
            'total_debe' => $debe,
            'total_haber'=> $haber,
            'diferencia' => $debe - $haber,
            'balanceado' => abs($debe - $haber) < 0.01,
        ];
    }

    // ─────────────────────────────────────────────
    // NUEVO: Balance filtrado por fechas
    // ─────────────────────────────────────────────
    private function calcularBalancePorFechas($periodoId, $desde, $hasta): array
    {
        $debe = DetalleAsiento::whereHas('asiento', function ($q) use ($periodoId, $desde, $hasta) {
            $q->where('periodo_id', $periodoId)->whereBetween('fecha', [$desde, $hasta]);
        })->sum('debe');

        $haber = DetalleAsiento::whereHas('asiento', function ($q) use ($periodoId, $desde, $hasta) {
            $q->where('periodo_id', $periodoId)->whereBetween('fecha', [$desde, $hasta]);
        })->sum('haber');

        return [
            'total_debe' => $debe,
            'total_haber'=> $haber,
            'diferencia' => $debe - $haber,
            'balanceado' => abs($debe - $haber) < 0.01,
        ];
    }

    // ─────────────────────────────────────────────
    // NUEVO: Saldos agrupados por cuenta en un rango
    // ─────────────────────────────────────────────
    private function calcularSaldosPorFechas($periodoId, $desde, $hasta)
    {
        $detalles = DetalleAsiento::with('cuenta')
            ->whereHas('asiento', function ($q) use ($periodoId, $desde, $hasta) {
                $q->where('periodo_id', $periodoId)
                  ->whereBetween('fecha', [$desde, $hasta]);
            })
            ->get();

        $agrupados = $detalles->groupBy('cuenta_id')->map(function ($items) {
            $cuenta      = $items->first()->cuenta;
            $totalDebe   = $items->sum('debe');
            $totalHaber  = $items->sum('haber');

            // Saldo final según naturaleza de la cuenta
            if ($cuenta->naturaleza === 'D') {          // Deudora
                $neto = $totalDebe - $totalHaber;
                $saldoFinalDebe  = max(0, $neto);
                $saldoFinalHaber = max(0, -$neto);
            } else {                                     // Acreedora
                $neto = $totalHaber - $totalDebe;
                $saldoFinalDebe  = max(0, -$neto);
                $saldoFinalHaber = max(0, $neto);
            }

            return [
                'cuenta_id'          => $cuenta->id,
                'codigo'             => $cuenta->codigo,
                'nombre'             => $cuenta->nombre,
                'tipo'               => $cuenta->tipo,
                'total_debe'         => $totalDebe,
                'total_haber'        => $totalHaber,
                'saldo_final_debe'   => $saldoFinalDebe,
                'saldo_final_haber'  => $saldoFinalHaber,
                'naturaleza'         => $cuenta->naturaleza,
                'permite_movimiento' => $cuenta->permite_movimiento,
                'nivel'              => $cuenta->nivel,
            ];
        });

        return $agrupados->values();
    }
}
