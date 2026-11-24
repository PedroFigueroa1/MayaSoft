<?php

namespace App\Http\Controllers;

use App\Models\Periodo;
use App\Models\Cuentas;
use App\Models\DetalleAsiento;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Empresa;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use App\Exports\BalanceGeneralExport;

class BalanceGeneralController extends Controller
{
    public function index(Request $request)
    {
        $periodos = Periodo::where('estado', 'A')->get();

        return Inertia::render('BalanceGeneral/Index', [
            'periodos' => $periodos,
            'filters'  => $request->only(['periodo_id']),
        ]);
    }

    public function generar(Request $request, Periodo $periodo)
    {
        $userId   = auth()->id();
        $nivelMax = $request->input('nivel_max', 5);
        $desde    = $request->input('desde');
        $hasta    = $request->input('hasta');

        $empresa = $periodo->empresa;

        $estructuraActivos     = $this->obtenerEstructuraJerarquica('A',  $periodo->id, $userId, $nivelMax, $desde, $hasta);
        $estructuraPasivos     = $this->obtenerEstructuraJerarquica('P',  $periodo->id, $userId, $nivelMax, $desde, $hasta);
        $estructuraPatrimonio  = $this->obtenerEstructuraJerarquica('PN', $periodo->id, $userId, $nivelMax, $desde, $hasta);

        $totalActivos           = $this->calcularTotalEstructura($estructuraActivos);
        $totalPasivos           = $this->calcularTotalEstructura($estructuraPasivos);
        $totalPatrimonio        = $this->calcularTotalEstructura($estructuraPatrimonio);
        $totalPasivosPatrimonio = $totalPasivos + $totalPatrimonio;
        $utilidadEjercicio      = $this->calcularUtilidadEjercicio($periodo->id, $userId, $desde, $hasta);

        return Inertia::render('BalanceGeneral/Reporte', [
            'periodo'               => $periodo,
            'empresa'               => $empresa,
            'estructuraActivos'     => $estructuraActivos,
            'estructuraPasivos'     => $estructuraPasivos,
            'estructuraPatrimonio'  => $estructuraPatrimonio,
            'totalActivos'          => $totalActivos,
            'totalPasivos'          => $totalPasivos,
            'totalPatrimonio'       => $totalPatrimonio,
            'totalPasivosPatrimonio'=> $totalPasivosPatrimonio,
            'utilidadEjercicio'     => $utilidadEjercicio,
            'nivelMax'              => (int) $nivelMax,
            'desde'                 => $desde,
            'hasta'                 => $hasta,
            'flash'                 => ['success' => 'Balance General generado correctamente'],
        ]);
    }

    // ==========================================
    // PRIVADOS
    // ==========================================

    private function obtenerEstructuraJerarquica($tipo, $periodoId, $userId, $nivelMax = 5, $desde = null, $hasta = null)
    {
        $cuentas = Cuentas::visiblePara($userId)
            ->where('tipo', $tipo)
            ->orderBy('codigo')
            ->get();

        $cuentasConSaldos = [];

        foreach ($cuentas as $cuenta) {
            $saldo = $this->calcularSaldoCuenta($cuenta->id, $periodoId, $desde, $hasta);

            $cuentasConSaldos[] = [
                'id'                => $cuenta->id,
                'codigo'            => $cuenta->codigo,
                'nombre'            => $cuenta->nombre,
                'tipo'              => $cuenta->tipo,
                'nivel'             => $cuenta->nivel,
                'cuenta_padre_id'   => $cuenta->cuenta_padre_id,
                'permite_movimiento'=> $cuenta->permite_movimiento,
                'es_base'           => $cuenta->es_base,
                'saldo'             => $saldo,
                'hijos'             => [],
            ];
        }

        $jerarquia = $this->construirJerarquia($cuentasConSaldos);
        return $this->podarJerarquia($jerarquia, $nivelMax, 1);
    }

    private function construirJerarquia($cuentas)
    {
        $jerarquia    = [];
        $cuentasPorId = [];

        foreach ($cuentas as $cuenta) {
            $cuentasPorId[$cuenta['id']] = $cuenta;
        }

        foreach ($cuentas as $cuenta) {
            if ($cuenta['cuenta_padre_id'] === null) {
                $jerarquia[] = &$cuentasPorId[$cuenta['id']];
            } else {
                if (isset($cuentasPorId[$cuenta['cuenta_padre_id']])) {
                    $cuentasPorId[$cuenta['cuenta_padre_id']]['hijos'][] = &$cuentasPorId[$cuenta['id']];
                }
            }
        }

        $this->calcularSaldosPadres($jerarquia);

        return $jerarquia;
    }

    private function calcularSaldosPadres(&$cuentas)
    {
        foreach ($cuentas as &$cuenta) {
            if (!empty($cuenta['hijos'])) {
                $this->calcularSaldosPadres($cuenta['hijos']);

                if (!$cuenta['permite_movimiento']) {
                    $cuenta['saldo'] = array_sum(array_column($cuenta['hijos'], 'saldo'));
                }
            }
        }
    }

    private function tieneHijosConSaldo($cuentaId, $periodoId, $userId, $nivelMax = 5, $desde = null, $hasta = null)
    {
        $hijos = Cuentas::visiblePara($userId)
            ->where('cuenta_padre_id', $cuentaId)
            ->when($nivelMax < 5, fn($q) => $q->where('nivel', '<=', $nivelMax))
            ->get();

        foreach ($hijos as $hijo) {
            $saldoHijo = $this->calcularSaldoCuenta($hijo->id, $periodoId, $desde, $hasta);
            if ($saldoHijo != 0 || $this->tieneHijosConSaldo($hijo->id, $periodoId, $userId, $nivelMax, $desde, $hasta)) {
                return true;
            }
        }

        return false;
    }

    private function calcularSaldoCuenta($cuentaId, $periodoId, $desde = null, $hasta = null)
    {
        $cuenta = Cuentas::find($cuentaId);

        if (!$cuenta->permite_movimiento) {
            return 0;
        }

        $query = DetalleAsiento::select(
                DB::raw('SUM(CASE WHEN cuentas.tipo IN ("A", "G")
                    THEN (detalle_asientos.debe - detalle_asientos.haber)
                    ELSE (detalle_asientos.haber - detalle_asientos.debe)
                    END) as saldo')
            )
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->join('cuentas', 'detalle_asientos.cuenta_id', '=', 'cuentas.id')
            ->where('detalle_asientos.cuenta_id', $cuentaId)
            ->where('asientos.periodo_id', $periodoId);

        if ($desde) {
            $query->where('asientos.fecha', '>=', $desde);
        }
        if ($hasta) {
            $query->where('asientos.fecha', '<=', $hasta);
        }

        $result = $query->first();

        return $result->saldo ?? 0;
    }

    private function calcularTotalEstructura($estructura)
    {
        $total = 0;
        foreach ($estructura as $cuenta) {
            $total += $cuenta['saldo'];
        }
        return $total;
    }

    private function calcularUtilidadEjercicio($periodoId, $userId, $desde = null, $hasta = null)
    {
        $ingresos = $this->calcularSaldoPorTipo('I', $periodoId, $userId, $desde, $hasta);
        $gastos   = $this->calcularSaldoPorTipo('G', $periodoId, $userId, $desde, $hasta);
        return $ingresos - $gastos;
    }

    private function calcularSaldoPorTipo($tipo, $periodoId, $userId, $desde = null, $hasta = null)
    {
        $query = DetalleAsiento::select(
                DB::raw('SUM(CASE WHEN cuentas.tipo = "I"
                    THEN detalle_asientos.haber - detalle_asientos.debe
                    ELSE detalle_asientos.debe - detalle_asientos.haber
                    END) as saldo')
            )
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->join('cuentas', 'detalle_asientos.cuenta_id', '=', 'cuentas.id')
            ->where('cuentas.tipo', $tipo)
            ->where('asientos.periodo_id', $periodoId)
            ->where(function($q) use ($userId) {
                $q->where('cuentas.user_id', $userId)
                  ->orWhere('cuentas.es_base', true);
            });

        if ($desde) {
            $query->where('asientos.fecha', '>=', $desde);
        }
        if ($hasta) {
            $query->where('asientos.fecha', '<=', $hasta);
        }

        $result = $query->first();

        return $result->saldo ?? 0;
    }

    // ==========================================
    // EXPORTAR PDF
    // ==========================================

    public function exportarPdf(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'nivel_max'  => 'nullable|integer|min:1|max:5',
            'desde'      => 'nullable|date',
            'hasta'      => 'nullable|date|after_or_equal:desde',
        ]);

        $userId   = auth()->id();
        $periodo  = Periodo::findOrFail($request->periodo_id);
        $empresa  = $periodo->empresa;
        $nivelMax = $request->input('nivel_max', 5);
        $desde    = $request->input('desde');
        $hasta    = $request->input('hasta');

        $estructuraActivos     = $this->obtenerEstructuraJerarquica('A',  $periodo->id, $userId, $nivelMax, $desde, $hasta);
        $estructuraPasivos     = $this->obtenerEstructuraJerarquica('P',  $periodo->id, $userId, $nivelMax, $desde, $hasta);
        $estructuraPatrimonio  = $this->obtenerEstructuraJerarquica('PN', $periodo->id, $userId, $nivelMax, $desde, $hasta);

        $totalActivos           = $this->calcularTotalEstructura($estructuraActivos);
        $totalPasivos           = $this->calcularTotalEstructura($estructuraPasivos);
        $totalPatrimonio        = $this->calcularTotalEstructura($estructuraPatrimonio);
        $totalPasivosPatrimonio = $totalPasivos + $totalPatrimonio;
        $utilidadEjercicio      = $this->calcularUtilidadEjercicio($periodo->id, $userId, $desde, $hasta);

        $data = [
            'periodo'               => $periodo,
            'empresa'               => $empresa,
            'estructuraActivos'     => $estructuraActivos,
            'estructuraPasivos'     => $estructuraPasivos,
            'estructuraPatrimonio'  => $estructuraPatrimonio,
            'totalActivos'          => $totalActivos,
            'totalPasivos'          => $totalPasivos,
            'totalPatrimonio'       => $totalPatrimonio,
            'totalPasivosPatrimonio'=> $totalPasivosPatrimonio,
            'utilidadEjercicio'     => $utilidadEjercicio,
            'nivelMax'              => $nivelMax,
            'desde'                 => $desde,
            'hasta'                 => $hasta,
            'fechaGeneracion'       => now()->format('d/m/Y H:i:s'),
        ];

        $pdf      = Pdf::loadView('pdf.balance-general', $data);
        $filename = 'balance-general-' . $periodo->nombre . '.pdf';
        $pdfPath  = storage_path('app/temp/' . $filename);

        $this->ensureDirectoryExists(storage_path('app/temp/'));
        $pdf->save($pdfPath);

        $downloadUrl = URL::temporarySignedRoute(
            'balance-general.download-pdf',
            now()->addMinutes(30),
            ['filename' => $filename]
        );

        return response()->json([
            'download_url' => $downloadUrl,
            'filename'     => $filename,
        ]);
    }

    public function downloadPdf(Request $request, $filename)
    {
        if (!$request->hasValidSignature()) abort(401);

        $filePath = storage_path('app/temp/' . $filename);

        if (!file_exists($filePath)) abort(404);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    private function ensureDirectoryExists($path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }

    // Métodos auxiliares que no cambian
    private function obtenerResumenCentrosCostos($periodoId)
    {
        return DetalleAsiento::select(
                'centro_costos.id',
                'centro_costos.codigo',
                'centro_costos.nombre',
                DB::raw('SUM(detalle_asientos.debe - detalle_asientos.haber) as total')
            )
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->join('centro_costos', 'detalle_asientos.centro_costos_id', '=', 'centro_costos.id')
            ->where('asientos.periodo_id', $periodoId)
            ->whereNotNull('detalle_asientos.centro_costos_id')
            ->groupBy('centro_costos.id', 'centro_costos.codigo', 'centro_costos.nombre')
            ->get();
    }

    private function obtenerResumenDeducibles($periodoId)
    {
        $resumen = DetalleAsiento::select(
                DB::raw('SUM(CASE WHEN deducible = true THEN detalle_asientos.debe ELSE 0 END) as total_deducible'),
                DB::raw('SUM(CASE WHEN deducible = false THEN detalle_asientos.debe ELSE 0 END) as total_no_deducible')
            )
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->join('cuentas', 'detalle_asientos.cuenta_id', '=', 'cuentas.id')
            ->where('asientos.periodo_id', $periodoId)
            ->where('cuentas.tipo', 'G')
            ->first();

        return [
            'total_deducible'     => $resumen->total_deducible ?? 0,
            'total_no_deducible'  => $resumen->total_no_deducible ?? 0,
        ];
    }

    private function podarJerarquia($cuentas, $nivelMax, $nivelActual)
    {
        $resultado = [];

        foreach ($cuentas as $cuenta) {
            if ($nivelActual < $nivelMax) {
                $cuenta['hijos'] = $this->podarJerarquia($cuenta['hijos'], $nivelMax, $nivelActual + 1);
            } else {
                $cuenta['hijos'] = [];
            }

            $tieneSaldoNoCero   = abs($cuenta['saldo']) > 1e-8;
            $tieneHijosVisibles = !empty($cuenta['hijos']);

            if (!$tieneSaldoNoCero && !$tieneHijosVisibles) {
                continue;
            }

            $resultado[] = $cuenta;
        }

        return $resultado;
    }

    public function exportarExcel(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'nivel_max'  => 'nullable|integer|min:1|max:5',
            'desde'      => 'nullable|date',
            'hasta'      => 'nullable|date|after_or_equal:desde',
        ]);

        $userId   = auth()->id();
        $periodo  = Periodo::findOrFail($request->periodo_id);
        $nivelMax = $request->input('nivel_max', 5);
        $desde    = $request->input('desde');
        $hasta    = $request->input('hasta');

        $estructuraActivos    = $this->obtenerEstructuraJerarquica('A',  $periodo->id, $userId, $nivelMax, $desde, $hasta);
        $estructuraPasivos    = $this->obtenerEstructuraJerarquica('P',  $periodo->id, $userId, $nivelMax, $desde, $hasta);
        $estructuraPatrimonio = $this->obtenerEstructuraJerarquica('PN', $periodo->id, $userId, $nivelMax, $desde, $hasta);

        $totalActivos           = $this->calcularTotalEstructura($estructuraActivos);
        $totalPasivos           = $this->calcularTotalEstructura($estructuraPasivos);
        $totalPatrimonio        = $this->calcularTotalEstructura($estructuraPatrimonio);
        $totalPasivosPatrimonio = $totalPasivos + $totalPatrimonio;
        $utilidadEjercicio      = $this->calcularUtilidadEjercicio($periodo->id, $userId, $desde, $hasta);

        $filename = (new BalanceGeneralExport(
            estructuraActivos:    $estructuraActivos,
            estructuraPasivos:    $estructuraPasivos,
            estructuraPatrimonio: $estructuraPatrimonio,
            totalActivos:         $totalActivos,
            totalPasivos:         $totalPasivos,
            totalPatrimonio:      $totalPatrimonio,
            totalPasivosPatrimonio: $totalPasivosPatrimonio,
            utilidadEjercicio:    $utilidadEjercicio,
            periodo:              $periodo,
            empresa:              $periodo->empresa,
        ))->generate();

        $downloadUrl = URL::temporarySignedRoute(
            'balance-general.download-excel',
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
}
