<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\LibroVenta;
use App\Models\LibroCompra;
use App\Models\Cuentas;
use App\Models\Periodo;
use App\Models\Asiento;
use App\Models\Almacen;
use App\Models\Producto;
use App\Models\DetalleAsiento;
use App\Helpers\AsientoHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Imports\VentasImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\Storage;
use App\Models\Importacion;

class LibroVentasController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // INDEX: listado de ventas del período con filtros
    // ──────────────────────────────────────────────────────────────
    public function index(Request $request, $periodoId)
    {
        $periodo = Periodo::findOrFail($periodoId);

        $query = LibroVenta::with([
                'cuentaIngreso', 'cuentaIvaDf', 'cuentaDescuento',
                'cuentaIvaDescuento', 'cuentaCobro', 'asiento'
            ])
            ->where('periodo_id', $periodoId)
            ->where('estado', 'pendiente');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('fecha_factura')) {
            $query->whereDate('fecha_factura', $request->fecha_factura);
        }
        if ($request->filled('buscar')) {
            $q = $request->buscar;
            $query->where(function($sub) use ($q) {
                $sub->where('razon_social_cliente', 'like', "%$q%")
                    ->orWhere('nit_cliente', 'like', "%$q%")
                    ->orWhere('nro_factura', 'like', "%$q%")
                    ->orWhere('especificacion', 'like', "%$q%");
            });
        }

        $ventas = $query->orderBy('fecha_factura')->paginate(500)->withQueryString();

        // Cuentas disponibles para los selectores (solo cuentas con movimiento)
        $cuentas = Cuentas::visiblePara(auth()->id())
            ->where('permite_movimiento', true)
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre', 'tipo']);

        // Obtener la cuenta predeterminada para IVA Débito Fiscal
        $cuentaIvaDfDefault = $this->getDefaultIvaDfAccount();

        // Totales para el resumen
        $totales = LibroVenta::where('periodo_id', $periodoId)->selectRaw('
            COUNT(*) as total_registros,
            SUM(importe_total) as total_importe,
            SUM(debito_fiscal) as total_iva,
            SUM(CASE WHEN estado = "contabilizado" THEN 1 ELSE 0 END) as total_contabilizados,
            SUM(CASE WHEN estado = "pendiente" THEN 1 ELSE 0 END) as total_pendientes
        ')->first();

        return Inertia::render('LibroVentas/Index', [
            'periodo'               => $periodo,
            'ventas'                => $ventas,
            'cuentas'               => $cuentas,
            'totales'               => $totales,
            'filters'               => $request->only(['estado', 'fecha_factura', 'buscar']),
            'cuenta_iva_df_default' => $cuentaIvaDfDefault,
        ]);
    }

    private function getDefaultIvaDfAccount()
    {
        // Busca la cuenta activa con nombre "IVA DÉBITO FISCAL"
        $cuenta = Cuentas::where('nombre', 'IVA DÉBITO FISCAL')
            ->where('permite_movimiento', true)
            ->first();

        if (!$cuenta) {
            throw new \Exception('No hay una cuenta de IVA Débito Fiscal configurada. Por favor, crea una cuenta con nombre "IVA DÉBITO FISCAL".');
        }

        return $cuenta;
    }
    private function getDefaultItExpenseAccount()
    {
        $cuenta = Cuentas::where('nombre', 'Impuesto a las Transacciones')
            ->where('permite_movimiento', true)
            ->first();

        if (!$cuenta) {
            throw new \Exception('No hay una cuenta de gasto "Impuesto a las Transacciones" configurada.');
        }
        return $cuenta;
    }

    private function getDefaultItPayableAccount()
    {
        $cuenta = Cuentas::where('nombre', 'Impuesto a las Transacciones por Pagar')
            ->where('permite_movimiento', true)
            ->first();

        if (!$cuenta) {
            throw new \Exception('No hay una cuenta de pasivo "Impuesto a las Transacciones por Pagar" configurada.');
        }
        return $cuenta;
    }

    // ──────────────────────────────────────────────────────────────
    // IMPORT: carga el Excel del SIN y guarda en libro_ventas
    // ──────────────────────────────────────────────────────────────

    public function importar(Request $request, $periodoId)
    {
        $request->validate([
            'archivos.*'        => 'required|file|mimes:xlsx,xls|max:10240',
            'archivos'          => 'required|array|max:5',
            'mes_importacion'   => 'nullable|string|max:20',
            'descripcion'       => 'nullable|string|max:255',
        ]);

        $periodo   = Periodo::findOrFail($periodoId);
        $archivos  = $request->file('archivos');
        $mesGlobal = $request->mes_importacion ?: ($periodo->nombre ?? null);

        $totalGlobalImportados = 0;
        $totalGlobalSaltados   = 0;
        $totalGlobalFallidos   = 0;
        $erroresGlobales       = [];

        foreach ($archivos as $archivo) {
            $nombreArchivo = $archivo->getClientOriginalName();

            // Crear registro de importación para este archivo
            $importacion = Importacion::create([
                'periodo_id'     => $periodo->id,
                'tipo'           => 'venta',
                'nombre_archivo' => $nombreArchivo,
                'ruta_archivo'   => $archivo->store('importaciones/ventas/' . date('Y/m'), 'public'),
                'tamano_bytes'   => $archivo->getSize(),
                'mes'            => $mesGlobal,          // ✅ el mes vive aquí, en importaciones
                'estado'         => 'procesando',
                'importado_por'  => auth()->id(),
                'log_error'      => null,
            ]);

            try {
                $spreadsheet = IOFactory::load($archivo->getPathname());
                $hoja        = $spreadsheet->getActiveSheet();
                $filas       = $hoja->toArray(null, true, true, false);

                // Buscar fila de encabezados
                $filaHeader = null;
                foreach ($filas as $idx => $fila) {
                    $filaUpper = array_map('strtoupper', array_filter($fila, fn($v) => is_string($v)));
                    if (in_array('NIT / CI CLIENTE', $filaUpper)) {
                        $filaHeader = $idx;
                        break;
                    }
                }

                if ($filaHeader === null) {
                    throw new \Exception("No se encontró la fila de encabezados en {$nombreArchivo}");
                }

                // Normalizar encabezados: trim + colapsar espacios + quitar tildes + º -> °
                $headers = array_map(function ($h) {
                    if (!is_string($h)) return '';
                    $h = trim(preg_replace('/\s+/u', ' ', $h));
                    $h = str_replace('º', '°', $h);
                    // Quitar tildes/diacríticos
                    $h = strtr($h, [
                        'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U',
                        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
                        'Ñ'=>'N','ñ'=>'n','Ü'=>'U','ü'=>'u',
                    ]);
                    return $h;
                }, $filas[$filaHeader]);

                $colMap = array_flip($headers);
                $col = fn(string $name) => $colMap[$name] ?? null;

                // Validar que existan las columnas mínimas
                $requeridas = ['NIT / CI CLIENTE', 'IMPORTE TOTAL DE LA VENTA', 'ESTADO'];
                $faltantes  = array_diff($requeridas, $headers);
                if (!empty($faltantes)) {
                    throw new \Exception(
                        "Faltan columnas requeridas en {$nombreArchivo}: " . implode(', ', $faltantes)
                    );
                }

                $importados = 0;
                $saltados   = 0;
                $fallidos   = 0;

                DB::transaction(function () use (
                    $filas,
                    $filaHeader,
                    $col,
                    $periodoId,
                    $importacion,
                    $nombreArchivo,
                    &$importados,
                    &$saltados,
                    &$fallidos
                ) {
                    for ($i = $filaHeader + 1; $i < count($filas); $i++) {
                        $fila = $filas[$i];

                        $nit = trim($fila[$col('NIT / CI CLIENTE')] ?? '');
                        if (empty($nit) || strtoupper($nit) === 'TOTALES') {
                            $saltados++;
                            continue;
                        }

                        $razon = trim($fila[$col('NOMBRE O RAZON SOCIAL')] ?? '');
                        if (stripos($razon, 'SIN MOVIMIENTO') !== false || stripos($razon, 'ANULADA') !== false) {
                            $saltados++;
                            continue;
                        }

                        $importeTotal = (float) ($fila[$col('IMPORTE TOTAL DE LA VENTA')] ?? 0);
                        if ($importeTotal <= 0) {
                            $saltados++;
                            continue;
                        }

                        $estado = trim($fila[$col('ESTADO')] ?? 'VALIDA');
                        if ($estado === 'ANULADA') {
                            $saltados++;
                            continue;
                        }

                        try {
                            // Fecha
                            $fechaRaw = trim($fila[$col('FECHA DE LA FACTURA')] ?? '');
                            $fecha = null;
                            if ($fechaRaw) {
                                if (is_numeric($fechaRaw)) {
                                    $fecha = Date::excelToDateTimeObject($fechaRaw)->format('Y-m-d');
                                } else {
                                    try {
                                        $fecha = \Carbon\Carbon::createFromFormat('d/m/Y', $fechaRaw)->format('Y-m-d');
                                    } catch (\Exception $e) {
                                        try {
                                            $fecha = \Carbon\Carbon::parse($fechaRaw)->format('Y-m-d');
                                        } catch (\Exception $e2) {}
                                    }
                                }
                            }

                            LibroVenta::create([
                                'periodo_id'            => $periodoId,
                                'importacion_id'        => $importacion->id,
                                'nro_correlativo'       => trim($fila[$col('N°')] ?? ''),
                                'especificacion'        => trim($fila[$col('ESPECIFICACION')] ?? ''),
                                'fecha_factura'         => $fecha,
                                'nro_factura'           => trim($fila[$col('N° DE LA FACTURA')] ?? ''),
                                'cod_autorizacion'      => trim($fila[$col('CODIGO DE AUTORIZACION')] ?? ''),
                                'nit_cliente'           => $nit,
                                'complemento'           => trim($fila[$col('COMPLEMENTO')] ?? ''),
                                'razon_social_cliente'  => trim(str_replace("\n", ' ', $razon)),
                                'importe_total'         => $importeTotal,
                                'importe_ice'           => (float) ($fila[$col('IMPORTE ICE')] ?? 0),
                                'importe_iehd'          => (float) ($fila[$col('IMPORTE IEHD')] ?? 0),
                                'importe_ipj'           => (float) ($fila[$col('IMPORTE IPJ')] ?? 0),
                                'tasas'                 => (float) ($fila[$col('TASAS')] ?? 0),
                                'otros_no_sujetos'      => (float) ($fila[$col('OTROS NO SUJETOS AL IVA')] ?? 0),
                                'exportaciones_exentas' => (float) ($fila[$col('EXPORTACIONES Y OPERACIONES EXENTAS')] ?? 0),
                                'ventas_tasa_cero'      => (float) ($fila[$col('VENTAS GRAVADAS A TASA CERO')] ?? 0),
                                'subtotal'              => (float) ($fila[$col('SUBTOTAL')] ?? 0),
                                'descuento'             => (float) ($fila[$col('DESCUENTOS BONIFICACIONES Y REBAJAS SUJETAS AL IVA')] ?? 0),
                                'importe_gift_card'     => (float) ($fila[$col('IMPORTE GIFT CARD')] ?? 0),
                                'importe_base_df'       => (float) ($fila[$col('IMPORTE BASE PARA DEBITO FISCAL')] ?? 0),
                                'debito_fiscal'         => (float) ($fila[$col('DEBITO FISCAL')] ?? 0),
                                'estado_factura'        => $estado,
                                'cod_control'           => trim($fila[$col('CODIGO DE CONTROL')] ?? ''),
                                'tipo_venta'            => trim($fila[$col('TIPO DE VENTA')] ?? ''),
                                'importe_debe'          => $importeTotal,
                                'importe_haber'         => round($importeTotal - (float) ($fila[$col('DEBITO FISCAL')] ?? 0), 4),
                                'estado'                => 'pendiente',
                                'importado_por'         => auth()->id(),
                                // ❌ 'mes' => ...  ← eliminado: el mes vive en importaciones
                            ]);

                            $importados++;
                        } catch (\Exception $e) {
                            $fallidos++;
                            Log::warning("Error en archivo {$nombreArchivo}, fila $i: " . $e->getMessage());
                        }
                    }
                });

                // Actualizar estadísticas de esta importación
                $importacion->update([
                    'total_registros'    => $importados + $saltados + $fallidos,
                    'registros_exitosos' => $importados,
                    'registros_fallidos' => $fallidos,
                    'estado'             => ($fallidos > 0 && $importados == 0) ? 'fallido' : 'completado',
                ]);

                $totalGlobalImportados += $importados;
                $totalGlobalSaltados   += $saltados;
                $totalGlobalFallidos   += $fallidos;

            } catch (\Exception $e) {
                $importacion->update([
                    'estado'    => 'fallido',
                    'log_error' => substr($e->getMessage(), 0, 1000),
                ]);
                $erroresGlobales[] = "Archivo '{$nombreArchivo}': " . $e->getMessage();
                Log::error("Error procesando archivo {$nombreArchivo}: " . $e->getMessage());
            }
        }

        $mensaje = "Procesamiento completado para {$totalGlobalImportados} registros importados, {$totalGlobalSaltados} saltados, {$totalGlobalFallidos} con error.";
        if (!empty($erroresGlobales)) {
            $mensaje .= ' Problemas en algunos archivos: ' . implode('; ', $erroresGlobales);
        }

        return back()->with('success', $mensaje);
    }

    public function indexImportacionesVentas($periodoId)
    {
        $importaciones = Importacion::with(['periodo', 'importadoPor'])
            ->where('periodo_id', $periodoId)
            ->where('tipo', 'venta')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('LibroVentas/IndexImportacion', [
            'importaciones' => $importaciones, // ← datos crudos, sin mapas ni agrupaciones
            'periodoId'     => $periodoId,
        ]);
    }
    public function getLibroVentas($id)
    {
        try {
            $libroVentas = LibroVenta::where('importacion_id', $id)
                ->orderBy('fecha_factura', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $libroVentas
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getLibroVentas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy($id)
    {
        try {
            $importacion = Importacion::findOrFail($id);

            if ($importacion->tipo === 'compra') {
                LibroCompra::where('importacion_id', $id)->delete();
            } elseif ($importacion->tipo === 'venta') {
                LibroVenta::where('importacion_id', $id)->delete();
            }

            if ($importacion->ruta_archivo && Storage::disk('public')->exists($importacion->ruta_archivo)) {
                Storage::disk('public')->delete($importacion->ruta_archivo);
            }

            $importacion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Importación eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error eliminando importación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }
    // ──────────────────────────────────────────────────────────────
    // UPDATE: actualizar cuentas asignadas en una fila
    // ──────────────────────────────────────────────────────────────
    public function actualizarCuentas(Request $request, $ventaId)
    {
        $venta = LibroVenta::findOrFail($ventaId);

        $request->validate([
            'cuenta_ingreso_id'        => 'nullable|exists:cuentas,id',
            'cuenta_iva_df_id'         => 'nullable|exists:cuentas,id',
            'cuenta_descuento_id'      => 'nullable|exists:cuentas,id',
            'cuenta_iva_descuento_id'  => 'nullable|exists:cuentas,id',
            'cuenta_cobro_id'          => 'nullable|exists:cuentas,id',
        ]);

        $venta->update($request->only([
            'cuenta_ingreso_id', 'cuenta_iva_df_id', 'cuenta_descuento_id',
            'cuenta_iva_descuento_id', 'cuenta_cobro_id'
        ]));

        return back()->with('success', 'Cuentas actualizadas.');
    }

    // ──────────────────────────────────────────────────────────────
    // CONTABILIZAR: genera el asiento contable y marca como contabilizado
    // ──────────────────────────────────────────────────────────────
    /**
     * Contabilizar una venta individual
     */
    public function contabilizar(Request $request, $ventaId)
    {
        $venta = LibroVenta::findOrFail($ventaId);   // ojo: aquí $venta es el registro del LIBRO

        if ($venta->estado === 'contabilizado') {
            return back()->withErrors(['error' => 'Este registro ya fue contabilizado. Asiento: ' . optional($venta->asiento)->numero_asiento]);
        }
        if (strtolower($venta->estado_factura ?? '') === 'anulada') {
            return back()->withErrors(['error' => 'La factura está anulada.']);
        }

        // Ya tiene venta con inventario → la regla la manda la Venta
        if ($venta->venta_id) {
            try {
                app(\App\Services\ContabilizarVentaService::class)
                    ->contabilizarVenta(\App\Models\Venta::findOrFail($venta->venta_id));
                return back()->with('success', 'Venta con inventario contabilizada (ingreso, costo e inventario).');
            } catch (\Exception $e) {
                return back()->withErrors(['error' => $e->getMessage()]);
            }
        }

        if (!$venta->cuenta_ingreso_id || !$venta->cuenta_cobro_id) {
            return back()->withErrors(['error' => 'Debe asignar la cuenta de Ingreso y la cuenta de Cobro antes de contabilizar.']);
        }

        try {
            $cuentaIvaDf     = $this->getDefaultIvaDfAccount();
            $cuentaItExpense = $this->getDefaultItExpenseAccount();
            $cuentaItPayable = $this->getDefaultItPayableAccount();

            DB::transaction(function () use ($venta, $cuentaIvaDf, $cuentaItExpense, $cuentaItPayable) {
                $periodo = $venta->periodo;

                $asiento = Asiento::create([
                    'periodo_id'     => $periodo->id,
                    'fecha'          => $venta->fecha_factura,
                    'numero_asiento' => AsientoHelper::generarNumeroAsiento($periodo->id, 'ING'),
                    'tipo'           => 'ING',
                    'glosa'          => ($venta->especificacion ?? 'Venta') . ' - ' . $venta->razon_social_cliente . ' Fact. ' . $venta->nro_factura,
                    'comprobante'    => $venta->nro_factura,
                    'creado_por'     => auth()->id(),
                ]);

                $descuento   = (float) $venta->descuento;
                $cobro       = round((float) $venta->importe_total - $descuento, 4);   // lo que realmente se cobra
                $ivaDf       = (float) $venta->debito_fiscal;
                $netoIngreso = round($cobro - $ivaDf, 4);
                $itMonto     = round((float) $venta->importe_total * 0.03, 2);

                $linea = fn($cuenta, $d, $h, $txt) => DetalleAsiento::create([
                    'asiento_id' => $asiento->id, 'cuenta_id' => $cuenta,
                    'debe' => $d, 'haber' => $h, 'descripcion' => $txt,
                ]);

                $linea($venta->cuenta_cobro_id,   $cobro, 0, 'Cobro de venta');            // DEBE: caja/banco/CxC
                if ($itMonto > 0) $linea($cuentaItExpense->id, $itMonto, 0, 'Impuesto a las Transacciones (3%)');
                $linea($venta->cuenta_ingreso_id, 0, $netoIngreso, 'Ingreso por venta');   // HABER: ingreso
                if ($ivaDf > 0)   $linea($cuentaIvaDf->id, 0, $ivaDf, 'IVA Débito Fiscal');
                if ($itMonto > 0) $linea($cuentaItPayable->id, 0, $itMonto, 'Impuesto a las Transacciones por Pagar');

                $venta->update([
                    'asiento_id'        => $asiento->id,
                    'estado'            => 'contabilizado',
                    'contabilizado_por' => auth()->id(),
                    'contabilizado_at'  => now(),
                ]);
            });

            return back()->with('success', 'Venta contabilizada correctamente.');

        } catch (\Exception $e) {
            Log::error('Error contabilizando venta ID ' . $venta->id . ': ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al contabilizar: ' . $e->getMessage()]);
        }
    }

    /**
     * Contabilizar ventas masivas
     */
    public function contabilizarMasivo(Request $request, $periodoId)
    {
        $ids = $request->input('ids', []);

        // Con venta (inventario) entran siempre; las manuales exigen cuentas
        $todas = LibroVenta::where('periodo_id', $periodoId)
            ->where('estado', 'pendiente')
            ->when(!empty($ids), fn($q) => $q->whereIn('id', $ids))
            ->where(function ($q) {
                $q->whereNotNull('venta_id')
                ->orWhere(fn($s) => $s->whereNotNull('cuenta_ingreso_id')->whereNotNull('cuenta_cobro_id'));
            })
            ->get()
            ->reject(fn($l) => strtolower($l->estado_factura ?? '') === 'anulada');

        if ($todas->isEmpty()) {
            return back()->withErrors(['error' => 'No hay ventas pendientes listas para contabilizar.']);
        }

        $conInventario = $todas->filter(fn($l) => $l->venta_id);
        $manuales      = $todas->reject(fn($l) => $l->venta_id);
        $errores = [];
        $okInv = 0;
        $okManual = 0;

        // A) Con inventario: un asiento por venta
        $svc = app(\App\Services\ContabilizarVentaService::class);
        foreach ($conInventario as $libro) {
            try {
                $svc->contabilizarVenta(\App\Models\Venta::findOrFail($libro->venta_id));
                $okInv++;
            } catch (\Throwable $e) {
                $errores[] = "Fact. {$libro->nro_factura}: " . $e->getMessage();
            }
        }

        // B) Manuales: asiento agrupado
        if ($manuales->isNotEmpty()) {
            try {
                $cuentaIvaDf     = $this->getDefaultIvaDfAccount();
                $cuentaItExpense = $this->getDefaultItExpenseAccount();
                $cuentaItPayable = $this->getDefaultItPayableAccount();

                DB::transaction(function () use ($manuales, $periodoId, $cuentaIvaDf, $cuentaItExpense, $cuentaItPayable) {
                    $periodo = Periodo::findOrFail($periodoId);

                    $asiento = Asiento::create([
                        'periodo_id'     => $periodo->id,
                        'fecha'          => $manuales->max('fecha_factura'),
                        'numero_asiento' => AsientoHelper::generarNumeroAsiento($periodo->id, 'ING'),
                        'tipo'           => 'ING',
                        'glosa'          => 'Contabilización masiva de ventas - ' . $manuales->count() . ' facturas',
                        'comprobante'    => 'MASIVO-' . now()->format('Ymd-His'),
                        'creado_por'     => auth()->id(),
                    ]);

                    $debe = []; $haber = []; $totalIva = 0; $totalIt = 0;
                    foreach ($manuales as $v) {
                        $cobro = (float) $v->importe_total - (float) $v->descuento;
                        $neto  = $cobro - (float) $v->debito_fiscal;
                        $debe[$v->cuenta_cobro_id]    = ($debe[$v->cuenta_cobro_id] ?? 0) + $cobro;      // DEBE cobro
                        $haber[$v->cuenta_ingreso_id] = ($haber[$v->cuenta_ingreso_id] ?? 0) + $neto;    // HABER ingreso
                        $totalIva += (float) $v->debito_fiscal;
                        $totalIt  += round((float) $v->importe_total * 0.03, 2);
                    }

                    $linea = fn($cuenta, $d, $h, $txt) => DetalleAsiento::create([
                        'asiento_id' => $asiento->id, 'cuenta_id' => $cuenta,
                        'debe' => round($d, 2), 'haber' => round($h, 2), 'descripcion' => $txt,
                    ]);

                    foreach ($debe as $c => $m)  if ($m != 0) $linea($c, $m, 0, 'Cobro de ventas (masivo)');
                    if ($totalIt > 0)            $linea($cuentaItExpense->id, $totalIt, 0, 'Impuesto a las Transacciones 3% (masivo)');
                    foreach ($haber as $c => $m) if ($m != 0) $linea($c, 0, $m, 'Ingresos por ventas (masivo)');
                    if ($totalIva > 0)           $linea($cuentaIvaDf->id, 0, $totalIva, 'IVA Débito Fiscal (masivo)');
                    if ($totalIt > 0)            $linea($cuentaItPayable->id, 0, $totalIt, 'Impuesto a las Transacciones por Pagar (masivo)');

                    $manuales->each(fn($v) => $v->update([
                        'asiento_id' => $asiento->id, 'estado' => 'contabilizado',
                        'contabilizado_por' => auth()->id(), 'contabilizado_at' => now(),
                    ]));
                });
                $okManual = $manuales->count();
            } catch (\Throwable $e) {
                Log::error('Error en contabilización masiva de ventas: ' . $e->getMessage());
                $errores[] = 'Ventas manuales: ' . $e->getMessage();
            }
        }

        $msg = "{$okInv} con inventario (asiento individual) y {$okManual} manuales (asiento agrupado) contabilizadas.";
        if ($errores) $msg .= ' Problemas: ' . implode(' | ', $errores);

        return back()->with($okInv + $okManual > 0 ? 'success' : 'error', $msg);
    }
    public function eliminarSeleccionados(Request $request, $periodoId)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'No se seleccionaron registros.');
        }

        $eliminados = LibroVenta::where('periodo_id', $periodoId)
                        ->whereIn('id', $ids)
                        ->where('estado', 'pendiente')
                        ->delete();

        if ($eliminados === 0) {
            return back()->with('error', 'No se encontraron registros pendientes para eliminar.');
        }

        return back()->with('success', "Se eliminaron $eliminados registros.");
    }

    // ──────────────────────────────────────────────────────────────
    // ANULAR: revierte la contabilización (elimina asiento)
    // ──────────────────────────────────────────────────────────────
    public function anular(LibroVenta $venta)
    {
    if ($venta->venta_id) {
        return back()->withErrors(['error' => 'Esta factura tiene venta con inventario. Anula la venta desde Inventarios.']);
    }

        try {
            DB::transaction(function () use ($venta) {
                if ($venta->asiento_id) {
                    $asiento = Asiento::find($venta->asiento_id);
                    if ($asiento) {
                        $asiento->detalles()->delete();
                        $asiento->delete();
                    }
                }

                $venta->update([
                    'asiento_id'        => null,
                    'estado'            => 'pendiente',
                    'contabilizado_por' => null,
                    'contabilizado_at'  => null,
                ]);
            });

            return back()->with('success', 'Contabilización anulada. El asiento fue eliminado.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al anular: ' . $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // ELIMINAR: eliminar registros importados (solo si están pendientes)
    // ──────────────────────────────────────────────────────────────
    public function eliminar(LibroVenta $venta)
    {
        if ($venta->estado === 'contabilizado') {
            return back()->withErrors(['error' => 'No se puede eliminar un registro contabilizado. Primero anule la contabilización.']);
        }

        $venta->delete();
        return back()->with('success', 'Registro eliminado.');
    }

    // ──────────────────────────────────────────────────────────────
    // ELIMINAR IMPORTACIÓN COMPLETA: borrar todos los pendientes del período
    // ──────────────────────────────────────────────────────────────
    public function eliminarImportacion($periodoId)
    {
        $eliminados = LibroVenta::where('periodo_id', $periodoId)
            ->where('estado', 'pendiente')
            ->delete();

        return back()->with('success', "$eliminados registros pendientes eliminados.");
    }
    // ── Mostrar formulario de envío a inventario ─────────────────
    public function formInventario(LibroVenta $libro)
    {
        if ($libro->estaIngresadoAInventario() || $libro->asiento_id) {
            return redirect()->route('libro-ventas.index', $libro->periodo_id)
                ->withErrors(['error' => 'Esta factura ya está en inventario o contabilizada.']);
        }

        $empresaId = session('empresa_id') ?? $libro->periodo->empresa_id;

        $cliente = \App\Models\ProveedorCliente::where('empresa_id', $empresaId)
            ->where('nit', $libro->nit_cliente)
            ->first(['id', 'cuenta_por_cobrar_id', 'dias_credito']);

        return Inertia::render('LibroVentas/IngresarInventario', [
            'libro' => $libro->only([
                'id', 'periodo_id', 'nit_cliente', 'razon_social_cliente', 'nro_factura',
                'fecha_factura', 'importe_total', 'debito_fiscal', 'descuento',
                'cuenta_cobro_id', 'cuenta_iva_df_id', 'cuenta_ingreso_id', 'almacen_id',
            ]),
            'periodo'   => $libro->periodo()->select('id', 'nombre')->first(),
            'almacenes' => Almacen::orderBy('nombre')->get(),
            'productos' => Producto::select('id', 'codigo', 'descripcion', 'precio_venta', 'precio_costo',
                                'aplica_iva', 'controla_stock', 'metodo_costeo',
                                'cuenta_ingreso_id', 'cuenta_costo_venta_id', 'cuenta_activo_id')
                                ->orderBy('descripcion')->get(),
            'cuentas'   => Cuentas::where('permite_movimiento', true)
                                ->select('id', 'codigo', 'nombre')->orderBy('codigo')->get(),
            'cuenta_iva_df_default' => Cuentas::where('nombre', 'IVA DÉBITO FISCAL')
                                ->where('permite_movimiento', true)->value('id'),
            'cliente_cxc' => $cliente?->cuenta_por_cobrar_id
                                ? Cuentas::find($cliente->cuenta_por_cobrar_id, ['id', 'codigo', 'nombre'])
                                : null,
        ]);
    }

    public function ingresarInventario(
        Request $request,
        LibroVenta $libro,
        \App\Services\IngresarLibroVentaAInventarioService $svc
    ) {
        $data = $request->validate([
            'almacen_id'               => 'required|exists:almacenes,id',
            'forma_pago'               => 'nullable|in:contado,credito',
            'dias_credito'             => 'nullable|numeric|min:0',
            'cuenta_cobro_id'          => 'nullable|exists:cuentas,id',
            'cuenta_iva_df_id'         => 'nullable|exists:cuentas,id',
            'contabilizar_ahora'       => 'boolean',
            'lineas'                   => 'required|array|min:1',
            'lineas.*.producto_id'     => 'required|exists:productos,id',
            'lineas.*.cantidad'        => 'required|numeric|gt:0',
            'lineas.*.precio_unitario' => 'required|numeric|gt:0',
            'lineas.*.descuento'       => 'nullable|numeric|min:0',
        ]);

        try {
            $venta = $svc->ingresar(
                $libro,
                (int) $data['almacen_id'],
                $data['lineas'],
                auth()->id(),
                $data['forma_pago'] ?? null,
                isset($data['dias_credito']) && $data['dias_credito'] !== '' ? (int) round((float) $data['dias_credito']) : 0,
                isset($data['cuenta_cobro_id']) ? (int) $data['cuenta_cobro_id'] : null,
                isset($data['cuenta_iva_df_id']) ? (int) $data['cuenta_iva_df_id'] : null
            );
        } catch (\DomainException $e) {
            return back()->withErrors(['inventario' => $e->getMessage()]);
        } catch (\Throwable $e) {
            \Log::error('Error ingresando venta a inventario #' . $libro->id . ': ' . $e->getMessage());
            return back()->withErrors(['inventario' => 'Error: ' . $e->getMessage()]);
        }

        $mensaje = 'Factura ingresada a inventario.';

        if ($request->boolean('contabilizar_ahora')) {
            try {
                app(\App\Services\ContabilizarVentaService::class)->contabilizarVenta($venta);
                $mensaje .= ' Contabilizada correctamente.';
            } catch (\Throwable $e) {
                return redirect()->route('libro-ventas.index', $libro->periodo_id)
                    ->withErrors(['error' => 'Ingresó a inventario, pero no se pudo contabilizar: ' . $e->getMessage()]);
            }
        }

        return redirect()->route('libro-ventas.index', $libro->periodo_id)->with('success', $mensaje);
    }

}
