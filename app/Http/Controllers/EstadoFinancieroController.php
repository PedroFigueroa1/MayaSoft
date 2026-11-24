<?php

namespace App\Http\Controllers;

use App\Models\Periodo;
use App\Models\EstadoFinanciero;
use App\Models\Cuentas;
use App\Models\DetalleAsiento;
use Inertia\Inertia;
use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\Storage;

class EstadoFinancieroController extends Controller
{
    public function index()
    {
        return Inertia::render('EstadosFinancieros/Index', [
            'periodos' => Periodo::with('empresa')
                ->where('estado', 'A') // Solo períodos cerrados
                ->orderBy('fecha_inicio', 'desc')
                ->get(),
            'reportes' => EstadoFinanciero::with(['periodo.empresa', 'generadoPor'])
                ->latest()
                ->paginate(10),
            'tiposReporte' => [
                ['value' => 'BG', 'label' => 'Balance General'],
                ['value' => 'ER', 'label' => 'Estado de Resultados'],
                ['value' => 'FL', 'label' => 'Flujo de Efectivo'],
                ['value' => 'CP', 'label' => 'Cambios en el Patrimonio']
            ]
        ]);
    }

    public function generar(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'tipo' => 'required|in:BG,ER,FL,CP',
            'observaciones' => 'nullable|string|max:500'
        ]);

        $periodo = Periodo::with('empresa')->findOrFail($request->periodo_id);

        // Verificar si ya existe un reporte del mismo tipo para este período
        $existeReporte = EstadoFinanciero::where('periodo_id', $periodo->id)
            ->where('tipo', $request->tipo)
            ->exists();

        if ($existeReporte && !$request->has('sobreescribir')) {
            return back()->with('warning', 'Ya existe un reporte de este tipo para el período seleccionado. ¿Desea sobrescribirlo?');
        }

        // Generar datos según el tipo de reporte
        switch($request->tipo) {
            case 'BG':
                $data = $this->generarBalanceGeneral($periodo);
                $view = 'pdf.balance-general';
                break;
            case 'ER':
                $data = $this->generarEstadoResultados($periodo);
                $view = 'pdf.estado-resultados';
                break;
            case 'FL':
                $data = $this->generarFlujoEfectivo($periodo);
                $view = 'pdf.flujo-efectivo';
                break;
            case 'CP':
                $data = $this->generarCambiosPatrimonio($periodo);
                $view = 'pdf.cambios-patrimonio';
                break;
            default:
                return back()->with('error', 'Tipo de reporte no válido');
        }

        // Generar PDF
        $pdf = PDF::loadView($view, $data);
        $filename = "{$request->tipo}_{$periodo->empresa->codigo}_{$periodo->nombre}.pdf";
        $path = "public/estados_financieros/{$filename}";

        // Guardar en storage
        Storage::put($path, $pdf->output());

        // Eliminar reporte anterior si existe
        if ($existeReporte) {
            EstadoFinanciero::where('periodo_id', $periodo->id)
                ->where('tipo', $request->tipo)
                ->delete();
        }

        // Guardar registro en BD
        $reporte = EstadoFinanciero::create([
            'periodo_id' => $periodo->id,
            'tipo' => $request->tipo,
            'generado_por' => auth()->id(),
            'archivo_pdf' => str_replace('public/', '', $path),
            'observaciones' => $request->observaciones
        ]);

        return redirect()
            ->route('estados-financieros.index')
            ->with('success', 'Reporte generado correctamente');
    }

    public function descargar($id)
    {
        $reporte = EstadoFinanciero::findOrFail($id);
        $path = storage_path("app/private/public/{$reporte->archivo_pdf}");

        return response()->download($path);
    }

    protected function generarBalanceGeneral($periodo)
    {
        $activos = $this->obtenerCuentasConSaldo($periodo, 'A');
        $pasivos = $this->obtenerCuentasConSaldo($periodo, 'P');
        $patrimonio = $this->obtenerCuentasConSaldo($periodo, 'PN');

        return [
            'titulo' => 'Balance General',
            'empresa' => $periodo->empresa,
            'periodo' => $periodo,
            'activos' => $this->organizarJerarquiaCuentas($activos),
            'pasivos' => $this->organizarJerarquiaCuentas($pasivos),
            'patrimonio' => $this->organizarJerarquiaCuentas($patrimonio),
            'fechaGeneracion' => now()->format('d/m/Y H:i:s'),
            'totalActivos' => $activos->sum('saldo'),
            'totalPasivosPatrimonio' => $pasivos->sum('saldo') + $patrimonio->sum('saldo')
        ];
    }

    protected function generarEstadoResultados($periodo)
    {
        $ingresos = $this->obtenerCuentasConSaldo($periodo, 'I');
        $gastos = $this->obtenerCuentasConSaldo($periodo, 'G');

        $totalIngresos = $ingresos->sum('saldo');
        $totalGastos = $gastos->sum('saldo');
        $utilidad = $totalIngresos - $totalGastos;

        return [
            'titulo' => 'Estado de Resultados',
            'empresa' => $periodo->empresa,
            'periodo' => $periodo,
            'ingresos' => $this->organizarJerarquiaCuentas($ingresos),
            'gastos' => $this->organizarJerarquiaCuentas($gastos),
            'fechaGeneracion' => now()->format('d/m/Y H:i:s'),
            'totalIngresos' => $totalIngresos,
            'totalGastos' => $totalGastos,
            'utilidad' => $utilidad
        ];
    }

    protected function generarFlujoEfectivo($periodo)
    {
        // Implementación básica - ajustar según necesidades
        $efectivoOperacional = $this->obtenerCuentasConSaldo($periodo, 'A', ['EFECTIVO', 'BANCOS']);
        $inversiones = $this->obtenerCuentasConSaldo($periodo, 'A', ['INVERSIONES']);
        $financiamiento = $this->obtenerCuentasConSaldo($periodo, 'P', ['PRESTAMOS']);

        return [
            'titulo' => 'Flujo de Efectivo',
            'empresa' => $periodo->empresa,
            'periodo' => $periodo,
            'operacional' => $efectivoOperacional,
            'inversion' => $inversiones,
            'financiamiento' => $financiamiento,
            'fechaGeneracion' => now()->format('d/m/Y H:i:s')
        ];
    }

    protected function generarCambiosPatrimonio($periodo)
    {
        $patrimonio = $this->obtenerCuentasConSaldo($periodo, 'PN');
        $utilidad = $this->calcularUtilidadPeriodo($periodo);

        return [
            'titulo' => 'Cambios en el Patrimonio',
            'empresa' => $periodo->empresa,
            'periodo' => $periodo,
            'patrimonio' => $this->organizarJerarquiaCuentas($patrimonio),
            'utilidad' => $utilidad,
            'fechaGeneracion' => now()->format('d/m/Y H:i:s')
        ];
    }

    protected function obtenerCuentasConSaldo($periodo, $tipo, $filtros = [])
    {
        $query = Cuentas::where('tipo', $tipo)
            ->where('permite_movimiento', true);

        if (!empty($filtros)) {
            $query->where(function($q) use ($filtros) {
                foreach ($filtros as $filtro) {
                    $q->orWhere('nombre', 'like', "%$filtro%");
                }
            });
        }

        return $query->with(['movimientos' => function($q) use ($periodo) {
                $q->whereHas('asiento', function($q) use ($periodo) {
                    $q->where('periodo_id', $periodo->id);
                });
            }])
            ->get()
            ->map(function($cuenta) {
                $cuenta->saldo = $cuenta->movimientos->sum('debe') - $cuenta->movimientos->sum('haber');
                return $cuenta;
            });
    }

    protected function organizarJerarquiaCuentas($cuentas)
    {
        return $cuentas->whereNull('cuenta_padre_id')->map(function($cuenta) use ($cuentas) {
            $cuenta->hijos = $cuentas->where('cuenta_padre_id', $cuenta->id);
            $cuenta->total = $cuenta->saldo + $cuenta->hijos->sum('saldo');
            return $cuenta;
        });
    }

    protected function calcularUtilidadPeriodo($periodo)
    {
        $ingresos = $this->obtenerCuentasConSaldo($periodo, 'I')->sum('saldo');
        $gastos = $this->obtenerCuentasConSaldo($periodo, 'G')->sum('saldo');

        return $ingresos - $gastos;
    }
}
