<?php

namespace App\Http\Controllers;

use App\Models\Periodo;
use App\Models\Cuentas;
use App\Models\DetalleAsiento;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use App\Exports\EstadoResultadosExport;
use Carbon\Carbon;

class EstadoResultadosController extends Controller
{
    public function index(Request $request, Periodo $periodo)
    {
        return Inertia::render('EstadoResultados/Index', [
            'periodoActual' => $periodo,
            'filters' => ['periodo_id' => $periodo->id]
        ]);
    }

    public function generar(Request $request, Periodo $periodo)
    {
        try {
            $request->validate([
                'fecha_inicio' => 'nullable|date',
                'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            ]);

            $datos = $this->obtenerDatosReporte($periodo, $request);

            return Inertia::render('EstadoResultados/Reporte', [
                'periodo'            => $periodo,
                'empresa'            => $periodo->empresa,
                'fechaInicio'        => $datos['fechaInicio'],
                'fechaFin'           => $datos['fechaFin'],
                'estructuraIngresos' => $datos['estructuraIngresos'],
                'estructuraGastos'   => $datos['estructuraGastos'],
                'totalIngresos'      => $datos['totalIngresos'],
                'totalGastos'        => $datos['totalGastos'],
                'resultadoEjercicio' => $datos['resultadoEjercicio'],
                'flash'              => ['success' => 'Estado de Resultados generado correctamente']
            ]);

        } catch (\Exception $e) {
            \Log::error('Error EstadoResultados: ' . $e->getMessage());
            return redirect()->back()->withErrors([
                'message' => 'Error al generar: ' . $e->getMessage()
            ]);
        }
    }

    public function exportarPdf(Request $request)
    {
        $request->validate([
            'periodo_id'   => 'required|exists:periodos,id',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $periodo = Periodo::findOrFail($request->periodo_id);
        $datos   = $this->obtenerDatosReporte($periodo, $request);

        $pdf = Pdf::loadView('pdf.estado-resultados', $datos + ['periodo' => $periodo]);

        $filename = 'estado-resultados-' . $periodo->nombre . '-' . now()->timestamp . '.pdf';
        $tempPath = storage_path('app/temp/' . $filename);
        $this->ensureDirectoryExists(storage_path('app/temp'));
        $pdf->save($tempPath);

        $downloadUrl = URL::temporarySignedRoute(
            'estado-resultados.download-pdf',
            now()->addMinutes(30),
            ['filename' => $filename]
        );

        return response()->json(['download_url' => $downloadUrl]);
    }

    public function exportarExcel(Request $request, $periodo)
    {
        try {
            $request->validate([
                'fecha_inicio' => 'nullable|date',
                'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            ]);

            $periodo = Periodo::with('empresa')->findOrFail($periodo);
            $datos   = $this->obtenerDatosReporte($periodo, $request);

            $export = new EstadoResultadosExport(
                estructuraIngresos:  $datos['estructuraIngresos'],
                estructuraGastos:    $datos['estructuraGastos'],
                totalIngresos:       $datos['totalIngresos'],
                totalGastos:         $datos['totalGastos'],
                resultadoEjercicio:  $datos['resultadoEjercicio'],
                periodo:             $periodo,
                empresa:             $periodo->empresa,
                fechaInicio:         $datos['fechaInicio'],
                fechaFin:            $datos['fechaFin'],
            );

            $filename = $export->generate();

            $downloadUrl = URL::temporarySignedRoute(
                'estado-resultados.download-excel',
                now()->addMinutes(30),
                ['filename' => $filename]
            );

            return response()->json(['download_url' => $downloadUrl, 'filename' => $filename]);

        } catch (\Throwable $e) {
            \Log::error('Error Excel Export: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'error'   => 'Error al generar Excel',
                'detalle' => $e->getMessage(),
                'linea'   => $e->getLine()
            ], 500);
        }
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

    /* =================================================================
       PRIVADOS
       ================================================================= */

    /**
     * FIX CRÍTICO: Convertir todo a Carbon antes de comparar.
     * El modelo Periodo tiene casts 'date', así que fecha_inicio y fecha_cierre
     * son objetos Carbon. Si comparamos string "2025-01-01" contra Carbon
     * "2025-01-01 00:00:00", PHP hace comparación lexicográfica y el string
     * corto pierde, por eso "2025-01-01" < "2025-01-01 00:00:00" daba true.
     */
    private function obtenerDatosReporte(Periodo $periodo, Request $request): array
    {
        // Fechas del período como Carbon (ya lo son por el cast, pero nos aseguramos)
        $perIni = Carbon::parse($periodo->fecha_inicio)->startOfDay();
        $perFin = Carbon::parse($periodo->fecha_cierre)->endOfDay();

        // Fechas del request como Carbon
        $fechaInicio = $request->filled('fecha_inicio')
            ? Carbon::parse($request->fecha_inicio)->startOfDay()
            : $perIni->copy();

        $fechaFin = $request->filled('fecha_fin')
            ? Carbon::parse($request->fecha_fin)->endOfDay()
            : $perFin->copy();

        // Ahora comparamos objetos Carbon correctamente
        if ($fechaInicio->lt($perIni) || $fechaFin->gt($perFin)) {
            throw new \Exception('Las fechas deben estar dentro del período contable.');
        }

        $estructuraIngresos = $this->obtenerEstructuraJerarquica('I', $periodo->id, $fechaInicio, $fechaFin);
        $estructuraGastos   = $this->obtenerEstructuraJerarquica('G', $periodo->id, $fechaInicio, $fechaFin);

        $totalIngresos = collect($estructuraIngresos)->sum('saldo');
        $totalGastos   = collect($estructuraGastos)->sum('saldo');

        return [
            'fechaInicio'        => $fechaInicio->format('Y-m-d'),
            'fechaFin'           => $fechaFin->format('Y-m-d'),
            'estructuraIngresos' => $estructuraIngresos,
            'estructuraGastos'   => $estructuraGastos,
            'totalIngresos'      => $totalIngresos,
            'totalGastos'        => $totalGastos,
            'resultadoEjercicio' => $totalIngresos - $totalGastos,
        ];
    }

    private function obtenerSaldosPorTipo(string $tipo, int $periodoId, Carbon $fechaInicio, Carbon $fechaFin): array
    {
        return DetalleAsiento::select(
                'detalle_asientos.cuenta_id',
                DB::raw('SUM(CASE WHEN cuentas.tipo = "I" THEN detalle_asientos.haber - detalle_asientos.debe ELSE detalle_asientos.debe - detalle_asientos.haber END) as saldo')
            )
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->join('cuentas', 'detalle_asientos.cuenta_id', '=', 'cuentas.id')
            ->where('asientos.periodo_id', $periodoId)
            ->where('cuentas.tipo', $tipo)
            ->whereBetween('asientos.fecha', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')])
            ->groupBy('detalle_asientos.cuenta_id')
            ->pluck('saldo', 'cuenta_id')
            ->map(fn($v) => (float) $v)
            ->toArray();
    }

    private function obtenerEstructuraJerarquica(string $tipo, int $periodoId, Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $saldos = $this->obtenerSaldosPorTipo($tipo, $periodoId, $fechaInicio, $fechaFin);

        $cuentas  = Cuentas::where('tipo', $tipo)->orderBy('codigo')->get();
        $porPadre = $cuentas->groupBy('cuenta_padre_id');

        $activas = $cuentas->filter(fn($c) => $this->ramaTieneSaldo($c->id, $saldos, $porPadre));

        if ($activas->isEmpty()) {
            return [];
        }

        return $this->construirJerarquia($activas, $saldos, $porPadre);
    }

    private function ramaTieneSaldo(int $id, array $saldos, $porPadre): bool
    {
        if (($saldos[$id] ?? 0) != 0) return true;

        foreach ($porPadre[$id] ?? [] as $hijo) {
            if ($this->ramaTieneSaldo($hijo->id, $saldos, $porPadre)) return true;
        }
        return false;
    }

    private function construirJerarquia($cuentas, array $saldos, $porPadre, ?int $padreId = null): array
    {
        $resultado = [];

        foreach ($cuentas->where('cuenta_padre_id', $padreId)->sortBy('codigo') as $c) {
            $item = [
                'id'                 => $c->id,
                'codigo'             => $c->codigo,
                'nombre'             => $c->nombre,
                'tipo'               => $c->tipo,
                'nivel'              => $c->nivel,
                'cuenta_padre_id'    => $c->cuenta_padre_id,
                'permite_movimiento' => $c->permite_movimiento,
                'saldo'              => $saldos[$c->id] ?? 0,
                'hijos'              => []
            ];

            $item['hijos'] = $this->construirJerarquia($cuentas, $saldos, $porPadre, $c->id);

            if (!$c->permite_movimiento && count($item['hijos'])) {
                $item['saldo'] = collect($item['hijos'])->sum('saldo');
            }

            $resultado[] = $item;
        }

        return $resultado;
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }
}
