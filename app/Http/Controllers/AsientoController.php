<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Asiento;
use App\Models\Cuentas;
use App\Models\Empresa;
use App\Models\Periodo;
use App\Exports\LibroDiarioExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\DetalleAsiento;
use App\Models\CentroCosto;
use App\Helpers\AsientoHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use App\Imports\ComprasImport;
use App\Imports\VentasImport;

class AsientoController extends Controller
{
    public function index(Request $request, $periodoId)
    {
        $query = Asiento::with(['periodo', 'detalles.cuenta', 'detalles.centroCosto', 'creadoPor'])
            ->where('periodo_id', $periodoId);

        // Filtrar por número de asiento
        if ($request->filled('numero_asiento')) {
            $query->where('numero_asiento', 'like', '%' . $request->numero_asiento . '%');
        }

        // Filtrar por rango de fechas
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        // Filtrar por glosa
        if ($request->filled('glosa')) {
            $query->where('glosa', 'like', '%' . $request->glosa . '%');
        }

        $asientos = $query->orderBy('fecha', 'desc')
            ->orderBy('numero_asiento', 'desc')
            ->paginate(30)
            ->withQueryString(); // Mantiene los parámetros de filtro en la paginación

        return Inertia::render('Asientos/Index', [
            'asientos' => $asientos,
            'periodoId' => $periodoId,
            'filters' => $request->only(['numero_asiento', 'fecha_desde', 'fecha_hasta', 'glosa'])
        ]);
    }


    public function create($periodoId)
    {
        $periodo = Periodo::findOrFail($periodoId);
        $cuentas = Cuentas::visiblePara(auth()->id())
            ->where('permite_movimiento', true)
            ->orderBy('codigo')
            ->get();
        $centrosCostos = CentroCosto::orderBy('codigo')->get();

        // Generar número de asiento automático basado en el tipo por defecto
        $tipoPorDefecto = 'TRN';
        $numeroAsiento = AsientoHelper::generarNumeroAsiento($periodoId, $tipoPorDefecto);

        return Inertia::render('Asientos/Create', compact('periodo', 'cuentas', 'centrosCostos', 'numeroAsiento'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'fecha' => 'required|date',
            'tipo' => 'required|in:ING,EGR,TRN,AJT,CIE',
            'glosa' => 'required|string',
            'comprobante' => 'nullable|string|max:50',
            'detalles' => 'required|array|min:2',
            'detalles.*.cuenta_id' => 'required|exists:cuentas,id',
            'detalles.*.debe' => 'required_without:detalles.*.haber|numeric|min:0',
            'detalles.*.haber' => 'required_without:detalles.*.debe|numeric|min:0',
            'detalles.*.centro_costos_id' => 'nullable|exists:centro_costos,id',
            'detalles.*.deducible' => 'boolean',
        ]);

        try {
            return DB::transaction(function () use ($request) {

                $numeroAsiento = AsientoHelper::generarNumeroAsiento(
                        $request->periodo_id,
                        $request->tipo
                    );

                $asiento = Asiento::create([
                    'periodo_id' => $request->periodo_id,
                    'fecha' => $request->fecha,
                    'numero_asiento' => $numeroAsiento,
                    'tipo' => $request->tipo,
                    'glosa' => $request->glosa,
                    'comprobante' => $request->comprobante,
                    'creado_por' => auth()->id(),
                ]);

                $total_debe = 0;
                $total_haber = 0;

                foreach ($request->detalles as $detalle) {
                    $det = new DetalleAsiento([
                        'cuenta_id' => $detalle['cuenta_id'],
                        'debe' => $detalle['debe'] ?? 0,
                        'haber' => $detalle['haber'] ?? 0,
                        'descripcion' => $detalle['descripcion'] ?? null,
                        'centro_costos_id' => $detalle['centro_costos_id'] ?? null,
                        'deducible' => $detalle['deducible'] ?? false,
                    ]);

                    $asiento->detalles()->save($det);

                    $total_debe += $det->debe;
                    $total_haber += $det->haber;
                }

                if (round($total_debe, 2) !== round($total_haber, 2)) {
                    throw new \Exception(
                        'El asiento no está balanceado. Debe: ' . $total_debe . ' - Haber: ' . $total_haber
                    );
                }

                return redirect()
                    ->route('asientos.index', ['periodoId' => $request->periodo_id])
                    ->with('success', 'Asiento creado correctamente. Número: ' . $numeroAsiento);
            });

        } catch (\Exception $e) {
            return back()
                ->withErrors(['balance' => $e->getMessage()])
                ->withInput();
        }
    }

    // AsientoHelper.php

    public static function generarNumeroAsiento($periodoId, $tipo)
    {
        if (!array_key_exists($tipo, self::$prefijos)) {
            throw new \InvalidArgumentException("Tipo de asiento no válido: {$tipo}");
        }

        $periodo = Periodo::find($periodoId);
        if (!$periodo) {
            throw new \InvalidArgumentException("Período no encontrado: {$periodoId}");
        }

        $prefijo = self::$prefijos[$tipo];

        // 🔒 Lock pesimista: bloquea la fila hasta que termine la transacción
        $ultimoAsiento = Asiento::where('periodo_id', $periodoId)
            ->where('numero_asiento', 'LIKE', $prefijo . '%')
            ->orderByRaw("CAST(SUBSTRING(numero_asiento, ?) AS UNSIGNED) DESC", [strlen($prefijo) + 1])
            ->lockForUpdate()   // <-- esto es clave
            ->first();

        if (!$ultimoAsiento) {
            return $prefijo . '0001';
        }

        $numeroPart = substr($ultimoAsiento->numero_asiento, strlen($prefijo));
        $numeroPart = preg_replace('/[^0-9]/', '', $numeroPart);

        if (empty($numeroPart) || !is_numeric($numeroPart)) {
            return $prefijo . '0001';
        }

        $siguienteNumero = intval($numeroPart) + 1;
        $digitos = max(4, strlen((string)$siguienteNumero));

        return $prefijo . str_pad($siguienteNumero, $digitos, '0', STR_PAD_LEFT);
    }


    public function show(Asiento $asiento)
    {
        // Cargar el asiento con todas las relaciones necesarias en una sola consulta
        $asiento = Asiento::with([
            'periodo.empresa' => function($query) {
                $query->select('id', 'nombre', 'nit', 'direccion', 'telefono',
                            'ciudad', 'logo');
            },
            'detalles.cuenta' => function($query) {
                $query->select('id', 'codigo', 'nombre', 'tipo');
            },
            'detalles.centroCosto' => function($query) { // 👈 NUEVO: cargar centroCosto
                $query->select('id', 'codigo', 'nombre');
            },
            'creadoPor' => function($query) {
                $query->select('id', 'name', 'email');
            }
        ])->findOrFail($asiento->id);

        // Preparar datos de la empresa
        $empresa = $asiento->periodo->empresa
            ? $asiento->periodo->empresa->toArray()
            : [
                'nombre' => 'Empresa no especificada',
                'nit' => '',
                'direccion' => '',
                'telefono' => '',
                'ciudad' => '',
                'pais' => '',
                'logo' => '',
                'email' => '',
                'sitio_web' => ''
            ];

        return Inertia::render('Asientos/Show', [
            'asiento' => $asiento->makeHidden(['periodo.empresa']),
            'empresa' => $empresa
        ]);
    }

    public function edit(Asiento $asiento)
    {
        $asiento->load('detalles.cuenta', 'detalles.centroCosto');
        return Inertia::render('Asientos/Edit', [
            'asiento' => $asiento,
            'periodos' => Periodo::all(),
            'cuentas' => Cuentas::visiblePara(auth()->id())->where('permite_movimiento', true)->get(),
            'centrosCostos' => CentroCosto::activos()->get(),
        ]);
    }

    public function update(Request $request, Asiento $asiento)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'fecha' => 'required|date',
            'numero_asiento' => 'required|string|max:20',
            'tipo' => 'required|in:ING,EGR,TRN,AJT,CIE',
            'glosa' => 'required|string',
            'comprobante' => 'nullable|string|max:50',
            'detalles' => 'required|array|min:2',
            'detalles.*.cuenta_id' => 'required|exists:cuentas,id',
            'detalles.*.debe' => 'required_without:detalles.*.haber|numeric|min:0',
            'detalles.*.haber' => 'required_without:detalles.*.debe|numeric|min:0',
            'detalles.*.centro_costos_id' => 'nullable|exists:centro_costos,id', // 👈 NUEVO
            'detalles.*.deducible' => 'boolean',
        ]);

        return DB::transaction(function () use ($request, $asiento) {
            $asiento->update([
                'periodo_id' => $request->periodo_id,
                'fecha' => $request->fecha,
                'numero_asiento' => $request->numero_asiento,
                'tipo' => $request->tipo,
                'glosa' => $request->glosa,
                'comprobante' => $request->comprobante,
            ]);

            $asiento->detalles()->delete();

            $total_debe = 0;
            $total_haber = 0;

            foreach ($request->detalles as $detalle) {
                $det = new DetalleAsiento([
                    'cuenta_id' => $detalle['cuenta_id'],
                    'debe' => $detalle['debe'] ?? 0,
                    'haber' => $detalle['haber'] ?? 0,
                    'descripcion' => $detalle['descripcion'] ?? null,
                    'centro_costos_id' => $detalle['centro_costos_id'] ?? null, // 👈 NUEVO
                    'deducible' => $detalle['deducible'] ?? false,
                ]);

                $asiento->detalles()->save($det);

                $total_debe += $det->debe;
                $total_haber += $det->haber;
            }

            if (round($total_debe, 2) !== round($total_haber, 2)) {
                return back()->withErrors([
                    'balance' => 'El asiento no está balanceado. Debe: ' . $total_debe . ' - Haber: ' . $total_haber
                ]);
            }

            return redirect()->route('asientos.show', $asiento)
                ->with('success', 'Asiento actualizado correctamente');
        });
    }

    public function destroy(Asiento $asiento)
    {
        DB::transaction(function () use ($asiento) {
            $asiento->detalles()->delete();
            $asiento->delete();
        });

        return redirect()->route('asientos.index')
            ->with('success', 'Asiento eliminado correctamente');
    }

    //libro diario
    public function libroDiario(Request $request, $periodoId)
    {

        // Obtener el período con la empresa relacionada
        $periodo = Periodo::with('empresa')->findOrFail($periodoId);

        // Obtener asientos con relaciones optimizadas
        $asientos = Asiento::with(['detalles.cuenta', 'detalles.centroCosto', 'periodo'])
            ->where('periodo_id', $periodoId)
            ->orderBy('fecha')
            ->orderBy('numero_asiento')
            ->get();

        // Calcular totales
        $totalDebe = 0;
        $totalHaber = 0;

        foreach ($asientos as $asiento) {
            foreach ($asiento->detalles as $detalle) {
                $totalDebe += $detalle->debe;
                $totalHaber += $detalle->haber;
            }
        }

        // Preparar datos de la empresa
        $empresa = $periodo->empresa ? $periodo->empresa->only([
            'nombre', 'nit', 'direccion', 'telefono', 'ciudad', 'logo'
        ]) : [
            'nombre' => 'Empresa no especificada',
            'nit' => '',
            'direccion' => '',
            'telefono' => '',
            'ciudad' => '',
            'logo' => ''
        ];

        return Inertia::render('Asientos/Libro-diario', [
            'asientos' => $asientos,
            'periodo' => $periodo,
            'totalDebe' => $totalDebe,
            'totalHaber' => $totalHaber,
            'empresa' => $empresa,
            'filters' => $request->only(['periodo_id'])
        ]);
    }
    //exportar libro diario a excel
    public function excel($periodoId)
    {
        $data = $this->getLibroDiarioData($periodoId);

        return Excel::download(new LibroDiarioExport($data),
            "libro-diario-{$data['periodo']->nombre}.xlsx");
    }
    // Función para obtener datos del libro diario
    private function getLibroDiarioData($periodoId)
    {
        $periodo = Periodo::with('empresa')->findOrFail($periodoId);

        // Obtener asientos con relaciones
        $asientos = Asiento::with([
                'detalles.cuenta',
                'detalles.centro_costo',
                'detalles.creado_por',
                'periodo'
            ])
            ->where('periodo_id', $periodoId)
            ->orderBy('fecha')
            ->orderBy('numero_asiento')
            ->orderBy('id')
            ->get();

        // Calcular totales
        $totalDebe = 0;
        $totalHaber = 0;

        foreach ($asientos as $asiento) {
            foreach ($asiento->detalles as $detalle) {
                $totalDebe += $detalle->debe;
                $totalHaber += $detalle->haber;
            }
        }

        // Preparar datos de empresa
        $empresa = $periodo->empresa ? $periodo->empresa->only([
            'nombre', 'nit', 'direccion', 'telefono', 'ciudad', 'pais', 'logo'
        ]) : [
            'nombre' => 'Empresa no especificada',
            'nit' => '',
            'direccion' => '',
            'telefono' => '',
            'ciudad' => '',
            'pais' => '',
            'logo' => ''
        ];

        // Funciones helper
        $formatCurrency = function($value) {
            return number_format($value, 2, ',', '.');
        };

        $formatDate = function($dateString) {
            return $dateString ? \Carbon\Carbon::parse($dateString)->format('d/m/Y') : 'N/D';
        };

        $tipoText = function($tipo) {
            $texts = [
                'ING' => 'Ingreso',
                'EGR' => 'Egreso',
                'TRN' => 'Traspaso',
                'AJT' => 'Ajuste',
                'CIE' => 'Cierre'
            ];
            return $texts[$tipo] ?? $tipo;
        };

        return [
            'asientos' => $asientos,
            'periodo' => $periodo,
            'totalDebe' => $totalDebe,
            'totalHaber' => $totalHaber,
            'empresa' => (object) $empresa,
            'formatCurrency' => $formatCurrency,
            'formatDate' => $formatDate,
            'tipoText' => $tipoText,
            'fechaGeneracion' => now()->format('d/m/Y H:i'),
        ];
    }

public function exportarLibroDiarioPdf($periodoId)
{
        $periodo = Periodo::with('empresa')->findOrFail($periodoId);

        // Obtener asientos con relaciones
        $asientos = Asiento::with([
                'detalles.cuenta',
                'detalles.centro_costo',
                'detalles.creado_por',
                'periodo'
            ])
            ->where('periodo_id', $periodoId)
            ->orderBy('fecha')
            ->orderBy('numero_asiento')
            ->orderBy('id')
            ->get();

        // Calcular totales
        $totalDebe = 0;
        $totalHaber = 0;

        foreach ($asientos as $asiento) {
            foreach ($asiento->detalles as $detalle) {
                $totalDebe += $detalle->debe;
                $totalHaber += $detalle->haber;
            }
        }

        // Preparar datos de empresa
        $empresa = $periodo->empresa ? $periodo->empresa->only([
            'nombre', 'nit', 'direccion', 'telefono', 'ciudad', 'pais', 'logo'
        ]) : [
            'nombre' => 'Empresa no especificada',
            'nit' => '',
            'direccion' => '',
            'telefono' => '',
            'ciudad' => '',
            'pais' => '',
            'logo' => ''
        ];

        // Funciones helper
        $formatCurrency = function($value) {
            return number_format($value, 2, ',', '.');
        };

        $formatDate = function($dateString) {
            return $dateString ? \Carbon\Carbon::parse($dateString)->format('d/m/Y') : 'N/D';
        };

        $tipoText = function($tipo) {
            $texts = [
                'ING' => 'Ingreso',
                'EGR' => 'Egreso',
                'TRN' => 'Traspaso',
                'AJT' => 'Ajuste',
                'CIE' => 'Cierre'
            ];
            return $texts[$tipo] ?? $tipo;
        };
        //return view('pdf.libro-diario', compact(
        //    'asientos',
        //    'periodo',
        //    'totalDebe',
        //    'totalHaber',
        //    'empresa'
        //));

        // Generar PDF
    $pdf = \PDF::loadView('pdf.libro-diario', compact(
        'asientos',
        'periodo',
        'totalDebe',
        'totalHaber',
        'empresa'
    ));

    return $pdf->download('libro-diario-' . $periodo->nombre . '.pdf');
//
        //// Nombre del archivo
        //$nombreArchivo = 'libro-diario-' . $periodo->nombre . '-' . now()->format('Y-m-d') . '.pdf';
//
        //// IMPORTANTE: Forzar la descarga con headers correctos
        //return response($pdf->output(), 200, [
        //    'Content-Type' => 'application/pdf',
        //    'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '"',
        //    'Content-Length' => strlen($pdf->output()),
        //    'Cache-Control' => 'private, max-age=0, must-revalidate',
        //    'Pragma' => 'public'
        //]);
//
    //} catch (\Exception $e) {
    //    return back()->with('error', 'Error al generar el PDF: ' . $e->getMessage());
    //}
}
public function libroMayorIndex($periodoId)
{
    try {
        $periodo = Periodo::findOrFail($periodoId);

        $cuentas = Cuentas::visiblePara(auth()->id())
            ->with(['detalleAsientos.asiento' => function ($query) use ($periodo) {
                $query->where('periodo_id', $periodo->id)
                    ->whereBetween('fecha', [
                        $periodo->fecha_inicio,
                        $periodo->fecha_cierre // ← Cambiado a fecha_cierre
                    ]);
            }])
            ->orderBy('codigo')
            ->get();

        return Inertia::render('Asientos/Libro-mayor', [
            'periodo' => $periodo,
            'cuentas' => $cuentas,
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return redirect()->back()->withErrors([
            'message' => 'El período seleccionado no existe'
        ]);
    }
}





public function generarLibroMayor(Request $request)
{
    \Log::info('=== PETICIÓN RECIBIDA EN BACKEND ===');
    \Log::info('Request data: ', $request->all());
    \Log::info('Headers: ', $request->headers->all());

    // Respuesta de prueba inmediata
    return response()->json([
        'success' => true,
        'message' => '✅ Backend recibió la petición correctamente',
        'data_recibida' => $request->all(),
        'timestamp' => now()
    ]);
}
// Versión alternativa si el procedimiento devuelve solo un resultset
    public function generarLibroMayorSimple(Request $request)
    {
        $request->validate([
            'cuenta_id' => 'required|exists:cuentas,id',
            'periodo_id' => 'required|exists:periodos,id',
            'fecha_inicio' => 'required|date',
            'fecha_cierre' => 'required|date|after_or_equal:fecha_inicio'
        ]);

        try {
            // Si el procedimiento solo devuelve el texto del libro mayor
            $resultados = DB::select('CALL probar_libro_mayor(?, ?, ?, ?)', [
                $request->periodo_id,
                $request->cuenta_id,
                $request->fecha_inicio,
                $request->fecha_cierre
            ]);

            $cuenta = DB::table('cuentas')->find($request->cuenta_id);
            $periodo = DB::table('periodos')->find($request->periodo_id);

            return response()->json([
                'success' => true,
                'cuenta' => $cuenta,
                'periodo' => $periodo,
                'libro_mayor' => $resultados[0]->resultado ?? $resultados[0]->libro_mayor ?? '',
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

public function sebaasPerro()
{
    return Inertia::render('Asientos/Prueva', [
        'mensaje' => 'Hola desde Prueva',
        // Otros datos que quieras pasar al componente
    ]);
}




public function indexMayor($periodoId)
{
    $periodo = Periodo::where('id', $periodoId)
                     ->where('estado', 'A')
                     ->with('empresa') // Cargar la relación empresa
                     ->firstOrFail();

    $cuentas = Cuentas::visiblePara(auth()->id())->where('permite_movimiento', true)->get();

    return Inertia::render('Asientos/Pro', [
        'periodo' => $periodo,
        'cuentas' => $cuentas,
        'empresa' => $periodo->empresa // Pasar la empresa a la vista
    ]);
}

/**
     * Genera el reporte del libro mayor
     */
    public function generar(Request $request)
    {
        try {
            $validated = $request->validate([
                'periodo_id' => 'required|exists:periodos,id',
                'cuenta_id' => 'required|exists:cuentas,id',
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio'
            ]);

            $periodo = Periodo::findOrFail($request->periodo_id);
            $cuenta = Cuentas::findOrFail($request->cuenta_id);
            $empresa = Empresa::find($periodo->empresa_id);

            // CORRECCIÓN: Usar join en lugar de whereHas para poder ordenar
            $query = DetalleAsiento::select('detalle_asientos.*')
                ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
                ->with(['asiento', 'cuenta'])
                ->where('detalle_asientos.cuenta_id', $request->cuenta_id)
                ->where('asientos.periodo_id', $request->periodo_id);

            if ($request->fecha_inicio) {
                $query->where('asientos.fecha', '>=', $request->fecha_inicio);
            }

            if ($request->fecha_fin) {
                $query->where('asientos.fecha', '<=', $request->fecha_fin);
            }

            $query->orderBy('asientos.fecha')
                ->orderBy('asientos.numero_asiento');

            $detalles = $query->get();

            // Calcular saldos
            $saldo = 0;
            $movimientos = [];

            foreach ($detalles as $detalle) {
                if ($detalle->cuenta->tipo == 'A' || $detalle->cuenta->tipo == 'G') {
                    // Cuentas de activo y gastos: aumentan con el debe, disminuyen con el haber
                    $saldo += $detalle->debe - $detalle->haber;
                } else {
                    // Cuentas de pasivo, patrimonio neto e ingresos: aumentan con el haber, disminuyen con el debe
                    $saldo += $detalle->haber - $detalle->debe;
                }

                $movimientos[] = [
                    'fecha' => $detalle->asiento->fecha,
                    'numero_asiento' => $detalle->asiento->numero_asiento,
                    'glosa' => $detalle->asiento->glosa,
                    'debe' => $detalle->debe,
                    'haber' => $detalle->haber,
                    'saldo' => $saldo
                ];
            }

            // Obtener saldo inicial
            $saldoInicial = $this->calcularSaldoInicial($cuenta, $periodo, $request->fecha_inicio);

            return Inertia::render('Asientos/ReportePro', [
                'periodo' => $periodo,
                'cuenta' => $cuenta,
                'movimientos' => $movimientos,
                'saldoInicial' => $saldoInicial,
                'empresa' => $empresa,
                'filters' => $validated,
                'flash' => ['success' => 'Reporte generado correctamente']
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calcula el saldo inicial de la cuenta hasta la fecha de inicio seleccionada
     */
    private function calcularSaldoInicial($cuenta, $periodo, $fechaInicio = null)
    {
        // CORRECCIÓN: Usar join en lugar de whereHas
        $query = DetalleAsiento::select('detalle_asientos.*')
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->where('detalle_asientos.cuenta_id', $cuenta->id)
            ->where('asientos.periodo_id', $periodo->id);

        if ($fechaInicio) {
            $query->where('asientos.fecha', '<', $fechaInicio);
        }

        $detalles = $query->get();

        $saldo = 0;

        foreach ($detalles as $detalle) {
            if ($cuenta->tipo == 'A' || $cuenta->tipo == 'G') {
                // Cuentas de activo y gastos
                $saldo += $detalle->debe - $detalle->haber;
            } else {
                // Cuentas de pasivo, patrimonio neto e ingresos
                $saldo += $detalle->haber - $detalle->debe;
            }
        }

        return $saldo;
    }

    /**
     * Exporta el libro mayor a PDF
     */
    public function exportarPdf(Request $request)
    {
        $request->validate([
            'periodo_id' => 'required|exists:periodos,id',
            'cuenta_id' => 'required|exists:cuentas,id',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio'
        ]);

        $periodo = Periodo::findOrFail($request->periodo_id);
        $cuenta = Cuentas::findOrFail($request->cuenta_id);

        // Obtener datos
        $query = DetalleAsiento::select('detalle_asientos.*')
            ->join('asientos', 'detalle_asientos.asiento_id', '=', 'asientos.id')
            ->with(['asiento', 'cuenta'])
            ->where('detalle_asientos.cuenta_id', $request->cuenta_id)
            ->where('asientos.periodo_id', $request->periodo_id);

        if ($request->fecha_inicio) {
            $query->where('asientos.fecha', '>=', $request->fecha_inicio);
        }

        if ($request->fecha_fin) {
            $query->where('asientos.fecha', '<=', $request->fecha_fin);
        }

        $query->orderBy('asientos.fecha')
            ->orderBy('asientos.numero_asiento');

        $detalles = $query->get();

        // Calcular saldos
        $saldo = 0;
        $movimientos = [];

        foreach ($detalles as $detalle) {
            if ($detalle->cuenta->tipo == 'A' || $detalle->cuenta->tipo == 'G') {
                $saldo += $detalle->debe - $detalle->haber;
            } else {
                $saldo += $detalle->haber - $detalle->debe;
            }

            $movimientos[] = [
                'fecha' => $detalle->asiento->fecha,
                'numero_asiento' => $detalle->asiento->numero_asiento,
                'glosa' => $detalle->asiento->glosa,
                'debe' => $detalle->debe,
                'haber' => $detalle->haber,
                'saldo' => $saldo
            ];
        }

        // Obtener saldo inicial
        $saldoInicial = $this->calcularSaldoInicial($cuenta, $periodo, $request->fecha_inicio);

        // Calcular totales
        $totalDebe = collect($movimientos)->sum('debe');
        $totalHaber = collect($movimientos)->sum('haber');

        // Obtener saldo final
        $saldoFinal = $saldoInicial;
        foreach ($movimientos as $movimiento) {
            if ($cuenta->tipo == 'A' || $cuenta->tipo == 'G') {
                $saldoFinal += $movimiento['debe'] - $movimiento['haber'];
            } else {
                $saldoFinal += $movimiento['haber'] - $movimiento['debe'];
            }
        }

        // Datos para el PDF
        $data = [
            'periodo' => $periodo,
            'cuenta' => $cuenta,
            'movimientos' => $movimientos,
            'saldoInicial' => $saldoInicial,
            'totalDebe' => $totalDebe,
            'totalHaber' => $totalHaber,
            'saldoFinal' => $saldoFinal,
            'fechaInicio' => $request->fecha_inicio,
            'fechaFin' => $request->fecha_fin,
            'fechaGeneracion' => now()->format('d/m/Y H:i:s'),
        ];

        // Generar PDF
        $pdf = Pdf::loadView('pdf.libro-mayor', $data);

        // Nombre del archivo
        $filename = 'libro-mayor-' . $cuenta->codigo . '-' . $periodo->nombre . '.pdf';

        // Para Inertia, necesitamos una respuesta JSON con la URL de descarga
        // Guardar temporalmente el PDF
        $pdfPath = storage_path('app/temp/' . $filename);
        $this->ensureDirectoryExists(storage_path('app/temp/'));
        $pdf->save($pdfPath);

        // Crear URL firmada para descarga
        $downloadUrl = URL::temporarySignedRoute(
            'libro-mayor.download-pdf',
            now()->addMinutes(30),
            ['filename' => $filename]
        );

        return response()->json([
            'download_url' => $downloadUrl,
            'filename' => $filename
        ]);
    }

    /**
     * Descargar el PDF generado
     */
    public function downloadPdf(Request $request, $filename)
    {
        if (!$request->hasValidSignature()) {
            abort(401);
        }

        $filePath = storage_path('app/temp/' . $filename);

        if (!file_exists($filePath)) {
            abort(404);
        }

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    /**
     * Función auxiliar para crear directorio si no existe
     */
    private function ensureDirectoryExists($path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }
    ///exportar libro diario a pdf
    private function getSharedData($asiento)
    {
        $empresa = Empresa::first(); // Ajusta según tu lógica

        return [
            'asiento' => $asiento->load(['detalles.cuenta', 'detalles.centro_costo', 'periodo', 'creado_por']),
            'empresa' => $empresa,
            'formatCurrency' => function($value) {
                return 'Bs ' . number_format($value, 2, ',', '.');
            },
            'formatDate' => function($dateString) {
                return \Carbon\Carbon::parse($dateString)->format('d/m/Y');
            },
            'formatDateTime' => function($dateString) {
                return \Carbon\Carbon::parse($dateString)->format('d/m/Y H:i');
            },
            'tipoText' => function($tipo) {
                $texts = ['ING' => 'Ingreso', 'EGR' => 'Egreso', 'TRN' => 'Traspaso', 'AJT' => 'Ajuste', 'CIE' => 'Cierre'];
                return $texts[$tipo] ?? $tipo;
            },
            'tipoClass' => function($tipo) {
                $classes = [
                    'ING' => 'bg-green-100 text-green-800',
                    'EGR' => 'bg-red-100 text-red-800',
                    'TRN' => 'bg-blue-100 text-blue-800',
                    'AJT' => 'bg-yellow-100 text-yellow-800',
                    'CIE' => 'bg-purple-100 text-purple-800'
                ];
                return $classes[$tipo] ?? 'bg-gray-100 text-gray-800';
            },
            'totalDebe' => function($detalles) {
                return $detalles->sum('debe');
            },
            'totalHaber' => function($detalles) {
                return $detalles->sum('haber');
            },
            'totalDeducible' => function($detalles) {
                return $detalles->where('deducible', true)->sum('debe');
            },
            'tieneCentrosCostos' => function($detalles) {
                return $detalles->filter(fn($d) => $d->centro_costo)->count() > 0;
            },
            'centrosCostosUtilizados' => function($detalles) {
                $centros = [];
                foreach ($detalles as $detalle) {
                    if ($detalle->centro_costo) {
                        $centroId = $detalle->centro_costo->id;
                        $monto = ($detalle->debe ?? 0) + ($detalle->haber ?? 0);

                        if (!isset($centros[$centroId])) {
                            $centros[$centroId] = [
                                'id' => $centroId,
                                'codigo' => $detalle->centro_costo->codigo,
                                'nombre' => $detalle->centro_costo->nombre,
                                'total' => 0
                            ];
                        }
                        $centros[$centroId]['total'] += $monto;
                    }
                }
                return array_values($centros);
            }
        ];
    }

    // PARA DESCARGAR PDF
    public function downloadAsiento(Asiento $asiento)
    {
        $asiento->load(['detalles.cuenta', 'detalles.centro_costo', 'creadoPor']);
        $data = $this->getSharedData($asiento);

        // Para PDF, añadir ruta absoluta del logo si existe
        $logoBase64 = null;
        if ($data['empresa']->logo && Storage::disk('public')->exists($data['empresa']->logo)) {
            $path = Storage::disk('public')->path($data['empresa']->logo);
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data_uri = 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($path));
            $logoBase64 = $data_uri;
        }
        $data['logo_base64'] = $logoBase64;

        $pdf = Pdf::loadView('pdf.asiento', $data);
        $pdf->setPaper('A4', 'portrait');


        return $pdf->download("asiento-{$asiento->numero_asiento}.pdf");
    }

    // PARA PREVISUALIZACIÓN EN NAVEGADOR
    public function previewAsiento(Asiento $asiento)
    {
        $data = $this->getSharedData($asiento);

        return view('pdf.asiento-preview', $data);
    }

    // OPCIÓN: Para ver PDF en navegador sin descargar
    public function stream(Asiento $asiento)
    {
        $data = $this->getSharedData($asiento);

        if ($data['empresa']->logo) {
            $data['logo_path'] = storage_path('app/public/' . $data['empresa']->logo);
        }

        $pdf = Pdf::loadView('pdf.asiento', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream("asiento-{$asiento->numero_asiento}.pdf");
    }

    public function importarArchivo(Request $request, $periodoId)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv',
            'tipo' => 'required|in:compras,ventas'
        ]);

        try {
            if ($request->tipo === 'compras') {
                $import = new ComprasImport($periodoId);
            } else {
                $import = new VentasImport($periodoId);
            }

            Excel::import($import, $request->file('archivo'));

            $mensaje = "Se importaron {$import->getCantidad()} registros de {$request->tipo}.";
            if (method_exists($import, 'getErrores') && count($import->getErrores()) > 0) {
                $mensaje .= " Errores: " . implode('; ', $import->getErrores());
            }

            return redirect()->back()->with('success', $mensaje);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Error al importar: ' . $e->getMessage()]);
        }
    }


}
