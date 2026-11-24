<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\LibroCompra;
use App\Models\Cuentas;
use App\Models\Periodo;
use App\Models\Asiento;
use App\Models\DetalleAsiento;
use App\Helpers\AsientoHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Importacion;
use Illuminate\Support\Facades\Storage;
use App\Services\IngresarLibroAInventarioService;
use App\Models\{Almacen, Producto, ProveedorCliente, Compra, DetalleCompra, MovimientoInventario};

class LibroComprasController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // INDEX: listado de compras del período con filtros
    // ──────────────────────────────────────────────────────────────
    public function index(Request $request, $periodoId)
    {
        $periodo = Periodo::findOrFail($periodoId);

        $query = LibroCompra::with([
                'cuentaGasto',
                'cuentaPago',
                'asiento'
            ])
            ->where('periodo_id', $periodoId)
            ->where('estado', 'pendiente');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('mes')) {
            $query->where('mes', $request->mes);
        }
        if ($request->filled('buscar')) {
            $q = $request->buscar;
            $query->where(function($sub) use ($q) {
                $sub->where('razon_social', 'like', "%$q%")
                    ->orWhere('nit_proveedor', 'like', "%$q%")
                    ->orWhere('nro_factura', 'like', "%$q%")
                    ->orWhere('concepto', 'like', "%$q%");
            });
        }

        $compras = $query->orderBy('fecha_factura')->paginate(500)->withQueryString();

        // Cuentas disponibles para los selectores (solo cuentas con movimiento)
        $cuentas = Cuentas::visiblePara(auth()->id())
            ->where('permite_movimiento', true)
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre', 'tipo']);

        // Obtener la cuenta predeterminada para IVA Crédito Fiscal
        $cuentaIvaCfDefault = $this->getDefaultIvaCfAccount();

        // Totales para el resumen
        $totales = LibroCompra::where('periodo_id', $periodoId)->selectRaw('
            COUNT(*) as total_registros,
            SUM(importe_total) as total_importe,
            SUM(cf_iva_contable) as total_iva,
            SUM(CASE WHEN estado = "contabilizado" THEN 1 ELSE 0 END) as total_contabilizados,
            SUM(CASE WHEN estado = "pendiente" THEN 1 ELSE 0 END) as total_pendientes
        ')->first();

        return Inertia::render('LibroCompras/Index', [
            'periodo'           => $periodo,
            'compras'           => $compras,
            'cuentas'           => $cuentas,
            'totales'           => $totales,
            'filters'           => $request->only(['estado', 'mes', 'buscar']),
            'cuenta_iva_cf_default' => $cuentaIvaCfDefault,
        ]);
    }
    private function getDefaultIvaCfAccount()
    {
        // Ejemplo: buscar la primera cuenta activa con tipo 'IVA' y que permita movimiento
        $cuenta = Cuentas::where('nombre', 'IVA CRÉDITO FISCAL')
            ->where('permite_movimiento', true)
            ->first();

        if (!$cuenta) {
            // Fallback: podrías lanzar una excepción o crear un registro por defecto
            throw new \Exception('No hay una cuenta de IVA Crédito Fiscal configurada. Por favor, crea una cuenta con tipo "IVA".');
        }

        return $cuenta;
    }

    // ──────────────────────────────────────────────────────────────
    // IMPORT: carga el Excel del SIN y guarda en libro_compras
    // ──────────────────────────────────────────────────────────────
    public function importar(Request $request, $periodoId)
    {
        $request->validate([
            'archivos.*'        => 'required|file|mimes:xlsx,xls|max:10240',
            'archivos'          => 'required|array|max:5',
            'mes_importacion'   => 'nullable|string|max:20',
            'descripcion'       => 'nullable|string|max:255',
        ]);

        $periodo = Periodo::findOrFail($periodoId);
        $archivos = $request->file('archivos');
        $mesGlobal = $request->mes_importacion;

        $totalGlobalImportados = 0;
        $totalGlobalSaltados   = 0;
        $totalGlobalFallidos   = 0;
        $erroresGlobales = [];

        foreach ($archivos as $archivo) {
            // Crear registro de importación para este archivo
            $importacion = Importacion::create([
                'periodo_id'      => $periodo->id,
                'tipo'            => 'compra',
                'nombre_archivo'  => $archivo->getClientOriginalName(),
                'ruta_archivo'    => $archivo->store('importaciones/compras/' . date('Y/m'), 'public'),
                'tamano_bytes'    => $archivo->getSize(),
                'mes'             => $mesGlobal,
                'estado'          => 'procesando',
                'importado_por'   => auth()->id(),
                'log_error'       => null,
            ]);

            try {
                $spreadsheet = IOFactory::load($archivo->getPathname());
                $hoja = $spreadsheet->getSheetByName('LCompras')
                        ?? $spreadsheet->getActiveSheet();
                $filas = $hoja->toArray(null, true, true, false);

                // Buscar la fila de encabezados (buscamos "NIT PROVEEDOR")
                $filaHeader = null;
                foreach ($filas as $idx => $fila) {
                    $filaUpper = array_map('strtoupper', array_filter($fila, fn($v) => is_string($v)));
                    if (in_array('NIT PROVEEDOR', $filaUpper)) {
                        $filaHeader = $idx;
                        break;
                    }
                }

                if ($filaHeader === null) {
                    throw new \Exception('No se encontró la fila de encabezados en ' . $archivo->getClientOriginalName());
                }

                $headers = array_map('trim', $filas[$filaHeader]);
                $colMap  = array_flip($headers);
                $col = fn(string $name) => $colMap[$name] ?? null;

                $importados = 0;
                $saltados   = 0;
                $fallidos   = 0;
                $mesActual  = $mesGlobal; // Por defecto el mes de la request, puede sobrescribirse por columna MES

                DB::transaction(function () use ($filas, $filaHeader, $col, $periodoId, $importacion, &$importados, &$saltados, &$fallidos, &$mesActual) {
                    for ($i = $filaHeader + 1; $i < count($filas); $i++) {
                        $fila = $filas[$i];

                        // Actualizar mes si columna MES existe
                        $mesCol = $col('MES');
                        if ($mesCol !== null && !empty(trim($fila[$mesCol] ?? ''))) {
                            $mesActual = strtoupper(trim($fila[$mesCol]));
                        }

                        $nit = trim($fila[$col('NIT PROVEEDOR')] ?? '');
                        if (empty($nit) || strtoupper($nit) === 'TOTALES') {
                            $saltados++;
                            continue;
                        }

                        $razon = trim($fila[$col('RAZON SOCIAL PROVEEDOR')] ?? '');
                        if (stripos($razon, 'SIN MOVIMIENTO') !== false) {
                            $saltados++;
                            continue;
                        }

                        try {
                            // Convertir fecha
                            $fechaRaw = trim($fila[$col('FECHA DE FACTURA/DUI/DIM')] ?? '');
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

                            $importeTotal = (float)($fila[$col('IMPORTE TOTAL COMPRA')] ?? 0);
                            $cfIva        = (float)($fila[$col('CF IVA CONTABLE')] ?? 0);
                            $creditoFisc  = (float)($fila[$col('CREDITO FISCAL')] ?? 0);
                            $descuento    = (float)($fila[$col('DESCUENTOS/BONIFICACIONES /REBAJAS SUJETAS AL IVA')] ?? 0);

                            LibroCompra::create([
                                'periodo_id'       => $periodoId,
                                'importacion_id'   => $importacion->id,
                                'mes'              => $mesActual,
                                'especificacion'   => trim($fila[$col('ESPECIFICACION')] ?? ''),
                                'nit_proveedor'    => $nit,
                                'razon_social'     => trim(str_replace("\n", ' ', $razon)),
                                'cod_autorizacion' => trim($fila[$col('CODIGO DE AUTORIZACION')] ?? ''),
                                'nro_factura'      => trim($fila[$col('NUMERO FACTURA')] ?? ''),
                                'nro_dui_dim'      => trim($fila[$col('NUMERO DUI/DIM')] ?? ''),
                                'fecha_factura'    => $fecha,
                                'importe_total'    => $importeTotal,
                                'importe_ice'      => (float)($fila[$col('IMPORTE ICE')] ?? 0),
                                'importe_iehd'     => (float)($fila[$col('IMPORTE IEHD')] ?? 0),
                                'importe_ipj'      => (float)($fila[$col('IMPORTE IPJ')] ?? 0),
                                'tasas'            => (float)($fila[$col('TASAS')] ?? 0),
                                'otro_no_sujeto'   => (float)($fila[$col('OTRO NO SUJETO A CREDITO FISCAL')] ?? 0),
                                'importes_exentos' => (float)($fila[$col('IMPORTES EXENTOS')] ?? 0),
                                'compras_tasa_cero'=> (float)($fila[$col('IMPORTE COMPRAS GRAVADAS A TASA CERO')] ?? 0),
                                'subtotal'         => (float)($fila[$col('SUBTOTAL')] ?? 0),
                                'descuento'        => $descuento,
                                'importe_base_cf'  => (float)($fila[$col('IMPORTE BASE CF')] ?? 0),
                                'credito_fiscal'   => $creditoFisc,
                                'cf_iva_contable'  => $cfIva,
                                'tipo_compra'      => trim($fila[$col('TIPO COMPRA')] ?? ''),
                                'concepto'         => trim($fila[$col('CONCEPTO COMPRA')] ?? ''),
                                'importe_debe'     => round($importeTotal - $cfIva, 4),
                                'importe_haber'    => $importeTotal,
                                'estado'           => 'pendiente',
                                'importado_por'    => auth()->id(),
                            ]);

                            $importados++;
                        } catch (\Exception $e) {
                            $fallidos++;
                            Log::warning("Error en archivo {$archivo->getClientOriginalName()}, fila $i: " . $e->getMessage());
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

                // Acumular globales
                $totalGlobalImportados += $importados;
                $totalGlobalSaltados   += $saltados;
                $totalGlobalFallidos   += $fallidos;

            } catch (\Exception $e) {
                // Marcar esta importación como fallida
                $importacion->update([
                    'estado'    => 'fallido',
                    'log_error' => substr($e->getMessage(), 0, 1000),
                ]);
                $erroresGlobales[] = "Archivo '{$archivo->getClientOriginalName()}': " . $e->getMessage();
                Log::error("Error procesando archivo {$archivo->getClientOriginalName()}: " . $e->getMessage());
            }
        }

        // Mensaje final consolidado
        $mensaje = "Procesamiento completado para {$totalGlobalImportados} registros importados, {$totalGlobalSaltados} saltados, {$totalGlobalFallidos} con error.";
        if (!empty($erroresGlobales)) {
            $mensaje .= " Problemas en algunos archivos: " . implode('; ', $erroresGlobales);
        }

        return back()->with('success', $mensaje);
    }
    // ──────────────────────────────────────────────────────────────
    // UPDATE: actualizar cuentas asignadas en una fila
    // ──────────────────────────────────────────────────────────────
public function actualizarCuentas(Request $request, $periodoId, $compra)
{
    $compra = LibroCompra::findOrFail($compra);

    $request->validate([
        'cuenta_gasto_id'         => 'nullable|exists:cuentas,id',
        'cuenta_iva_cf_id'        => 'nullable|exists:cuentas,id',
        'cuenta_descuento_id'     => 'nullable|exists:cuentas,id',
        'cuenta_iva_descuento_id' => 'nullable|exists:cuentas,id',
        'cuenta_pago_id'          => 'nullable|exists:cuentas,id',
    ]);

    $compra->update($request->only([
        'cuenta_gasto_id', 'cuenta_iva_cf_id', 'cuenta_descuento_id',
        'cuenta_iva_descuento_id', 'cuenta_pago_id'
    ]));

    return back()->with('success', 'Cuentas actualizadas.');
}

    // ──────────────────────────────────────────────────────────────
    // CONTABILIZAR: genera el asiento contable y marca como contabilizado
    // ──────────────────────────────────────────────────────────────
    public function contabilizar(Request $request, $compraId)
    {
        $compra = LibroCompra::findOrFail($compraId);

        if ($compra->estado === 'contabilizado') {
            return back()->withErrors(['error' => 'Este registro ya fue contabilizado. Asiento: ' . optional($compra->asiento)->numero_asiento]);
        }
        if ($compra->compra_id) {   // ya fue a inventario → la regla la manda la Compra
            try {
                app(\App\Services\ContabilizarCompraService::class)
                    ->contabilizarCompra(\App\Models\Compra::findOrFail($compra->compra_id));
                return back()->with('success', 'Compra con inventario contabilizada por producto.');
            } catch (\DomainException $e) {
                return back()->withErrors(['error' => $e->getMessage()]);
            }
        }

        // Validar cuentas obligatorias (gasto y pago)
        if (!$compra->cuenta_gasto_id || !$compra->cuenta_pago_id) {
            return back()->withErrors(['error' => 'Debe asignar la cuenta de Gasto y la cuenta de Pago antes de contabilizar.']);
        }

        $cuentaIvaCf = $this->getDefaultIvaCfAccount();
        if (!$cuentaIvaCf) {
            return back()->withErrors(['error' => 'No se ha definido la cuenta de IVA Crédito Fiscal.']);
        }

        try {
            DB::transaction(function () use ($compra, $cuentaIvaCf) { // ← sin paréntesis extra
                $periodo = $compra->periodo;
                $numeroAsiento = AsientoHelper::generarNumeroAsiento($periodo->id, 'EGR');

                $asiento = Asiento::create([
                    'periodo_id'      => $periodo->id,
                    'fecha'           => $compra->fecha_factura,
                    'numero_asiento'  => $numeroAsiento,
                    'tipo'            => 'EGR',
                    'glosa'           => ($compra->concepto ?? 'Compra') . ' - ' . $compra->razon_social . ' Fact. ' . $compra->nro_factura,
                    'comprobante'     => $compra->nro_factura,
                    'creado_por'      => auth()->id(),
                ]);

                $netoGasto = $compra->importe_total - $compra->credito_fiscal - $compra->descuento;
                $ivaCf     = $compra->credito_fiscal;
                $total     = $compra->importe_total;

                // Línea 1: Debe a Gasto
                DetalleAsiento::create([
                    'asiento_id'  => $asiento->id,
                    'cuenta_id'   => $compra->cuenta_gasto_id,
                    'debe'        => $netoGasto,
                    'haber'       => 0,
                    'descripcion' => 'Costo de compra sin IVA',
                ]);

                // Línea 2: Debe a IVA Crédito Fiscal
                DetalleAsiento::create([
                    'asiento_id'  => $asiento->id,
                    'cuenta_id'   => $cuentaIvaCf->id,
                    'debe'        => $ivaCf,
                    'haber'       => 0,
                    'descripcion' => 'Crédito fiscal IVA',
                ]);

                // Línea 3: Haber a Cuenta de Pago
                DetalleAsiento::create([
                    'asiento_id'  => $asiento->id,
                    'cuenta_id'   => $compra->cuenta_pago_id,
                    'debe'        => 0,
                    'haber'       => $total,
                    'descripcion' => 'Pago de compra',
                ]);

                // Actualizar compra
                $compra->update([
                    'asiento_id'        => $asiento->id,
                    'estado'            => 'contabilizado',
                    'contabilizado_por' => auth()->id(),
                    'contabilizado_at'  => now(),
                ]);
            });

            return back()->with('success', 'Compra contabilizada correctamente.');

        } catch (\Exception $e) {
            Log::error('Error contabilizando compra ID ' . $compra->id . ': ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al contabilizar: ' . $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // CONTABILIZAR MASIVO: contabiliza todos los pendientes con cuentas
    // ──────────────────────────────────────────────────────────────
    public function contabilizarMasivo(Request $request, $periodoId)
    {
        $ids = $request->input('ids', []);

        // Con inventario (compra_id) entran siempre; sin inventario exigen cuentas
        $todas = LibroCompra::where('periodo_id', $periodoId)
            ->where('estado', 'pendiente')
            ->when(!empty($ids), fn($q) => $q->whereIn('id', $ids))
            ->where(function ($q) {
                $q->whereNotNull('compra_id')
                ->orWhere(fn($s) => $s->whereNotNull('cuenta_gasto_id')->whereNotNull('cuenta_pago_id'));
            })
            ->get();

        if ($todas->isEmpty()) {
            return back()->withErrors(['error' => 'No hay compras pendientes listas para contabilizar.']);
        }

        // ── Separar ──
        $conInventario = $todas->filter(fn($c) => $c->compra_id);
        $compras       = $todas->reject(fn($c) => $c->compra_id);   // las de gasto

        $errores = [];
        $okInv   = 0;

        // ── A) Con inventario: un asiento por factura, por producto ──
        $svc = app(\App\Services\ContabilizarCompraService::class);
        foreach ($conInventario as $libro) {
            try {
                $svc->contabilizarCompra(\App\Models\Compra::findOrFail($libro->compra_id));
                $okInv++;
            } catch (\Throwable $e) {
                $errores[] = "Fact. {$libro->nro_factura}: " . $e->getMessage();
            }
        }

        // ── B) Sin inventario: asiento agrupado de gasto (lógica de antes) ──
        $okGasto = 0;
        if ($compras->isNotEmpty()) {
            $cuentaIvaCf = $this->getDefaultIvaCfAccount();

            try {
                DB::transaction(function () use ($compras, $cuentaIvaCf, $periodoId) {
                    $periodo       = Periodo::findOrFail($periodoId);
                    $numeroAsiento = AsientoHelper::generarNumeroAsiento($periodo->id, 'EGR');

                    $asiento = Asiento::create([
                        'periodo_id'     => $periodo->id,
                        'fecha'          => $compras->max('fecha_factura'),
                        'numero_asiento' => $numeroAsiento,
                        'tipo'           => 'EGR',
                        'glosa'          => 'Contabilización masiva de compras - ' . $compras->count() . ' facturas',
                        'comprobante'    => 'MASIVO-' . now()->format('Ymd-His'),
                        'creado_por'     => auth()->id(),
                    ]);

                    $debe_gastos = [];
                    $haber_pagos = [];
                    foreach ($compras as $c) {
                        $neto = $c->importe_total - $c->credito_fiscal - $c->descuento;
                        $debe_gastos[$c->cuenta_gasto_id] = ($debe_gastos[$c->cuenta_gasto_id] ?? 0) + $neto;
                        $haber_pagos[$c->cuenta_pago_id]  = ($haber_pagos[$c->cuenta_pago_id] ?? 0) + $c->importe_total;
                    }
                    $totalIvaCf = $compras->sum('credito_fiscal');

                    foreach ($debe_gastos as $cuentaId => $monto) {
                        if ($monto == 0) continue;
                        DetalleAsiento::create(['asiento_id' => $asiento->id, 'cuenta_id' => $cuentaId,
                            'debe' => round($monto, 2), 'haber' => 0, 'descripcion' => 'Costo de compras sin IVA (masivo)']);
                    }
                    if ($totalIvaCf > 0) {
                        DetalleAsiento::create(['asiento_id' => $asiento->id, 'cuenta_id' => $cuentaIvaCf->id,
                            'debe' => round($totalIvaCf, 2), 'haber' => 0, 'descripcion' => 'Crédito fiscal IVA (masivo)']);
                    }
                    foreach ($haber_pagos as $cuentaId => $monto) {
                        if ($monto == 0) continue;
                        DetalleAsiento::create(['asiento_id' => $asiento->id, 'cuenta_id' => $cuentaId,
                            'debe' => 0, 'haber' => round($monto, 2), 'descripcion' => 'Pago de compras (masivo)']);
                    }

                    $compras->each(fn($c) => $c->update([
                        'asiento_id' => $asiento->id, 'estado' => 'contabilizado',
                        'contabilizado_por' => auth()->id(), 'contabilizado_at' => now(),
                    ]));
                });
                $okGasto = $compras->count();
            } catch (\Throwable $e) {
                Log::error('Error en contabilización masiva: ' . $e->getMessage());
                $errores[] = 'Compras sin inventario: ' . $e->getMessage();
            }
        }

        $msg = "{$okInv} con inventario (asiento individual) y {$okGasto} sin inventario (asiento agrupado) contabilizadas.";
        if ($errores) $msg .= ' Problemas: ' . implode(' | ', $errores);

        return back()->with($okInv + $okGasto > 0 ? 'success' : 'error', $msg);
    }
    public function eliminarSeleccionados(Request $request, $periodoId)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'No se seleccionaron registros.');
        }

        // Eliminar solo los que estén en estado pendiente
        $eliminados = LibroCompra::where('periodo_id', $periodoId)
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
    public function anular(LibroCompra $compra)
    {
        if ($compra->estado !== 'contabilizado') {
            return back()->withErrors(['error' => 'Solo se pueden anular registros contabilizados.']);
        }

        try {
            DB::transaction(function () use ($compra) {
                if ($compra->asiento_id) {
                    $asiento = Asiento::find($compra->asiento_id);
                    if ($asiento) {
                        $asiento->detalles()->delete();
                        $asiento->delete();
                    }
                }

                $compra->update([
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
    public function eliminar(LibroCompra $compra)
    {
        if ($compra->estado === 'contabilizado') {
            return back()->withErrors(['error' => 'No se puede eliminar un registro contabilizado. Primero anule la contabilización.']);
        }

        $compra->delete();
        return back()->with('success', 'Registro eliminado.');
    }

    // ──────────────────────────────────────────────────────────────
    // ELIMINAR IMPORTACIÓN COMPLETA: borrar todos los pendientes del período
    // ──────────────────────────────────────────────────────────────
    public function eliminarImportacion($periodoId)
    {
        $eliminados = LibroCompra::where('periodo_id', $periodoId)
            ->where('estado', 'pendiente')
            ->delete();

        return back()->with('success', "$eliminados registros pendientes eliminados.");
    }

    /**
     * Muestra la lista de importaciones agrupadas por mes.
     */
public function indexImportacionesCompras($periodoId)
{
    $importaciones = Importacion::with(['periodo', 'importadoPor'])
        ->where('periodo_id', $periodoId)
        ->where('tipo', 'compra')
        ->orderBy('created_at', 'desc')
        ->get();

    return Inertia::render('LibroCompras/IndexImportacion', [
        'importaciones' => $importaciones,  // ← datos crudos, sin mapear
        'periodoId'     => $periodoId,
    ]);
}

    /**
     * API: Obtiene el libro de compras de una importación específica.
     */
    public function getLibroCompras($id)
    {
        try {
            // Consulta mínima sin relaciones para probar
            $libroCompras = LibroCompra::with('asiento')
                ->where('importacion_id', $id)
                ->orderBy('fecha_factura', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $libroCompras
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getLibroCompras: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() // solo en desarrollo
            ], 500);
        }
    }
    public function formInventario(LibroCompra $libro)
    {
        if ($libro->estado_inventario === 'ingresado' || $libro->asiento_id) {
            return redirect()->route('libro-compras.index', $libro->periodo_id)
                ->withErrors(['error' => 'Esta factura ya está en inventario o contabilizada.']);
        }

        $empresaId = session('empresa_id') ?? $libro->periodo->empresa_id;

        // CxP del proveedor (si ya existe como proveedor)
        $proveedor = ProveedorCliente::where('empresa_id', $empresaId)
            ->where('nit', $libro->nit_proveedor)
            ->first(['id', 'cuenta_por_pagar_id', 'dias_credito']);

        $proveedorCxp = $proveedor?->cuenta_por_pagar_id
            ? Cuentas::find($proveedor->cuenta_por_pagar_id, ['id', 'codigo', 'nombre'])
            : null;

        return Inertia::render('LibroCompras/IngresarInventario', [
            'libro' => $libro->only([
                'id', 'periodo_id', 'nit_proveedor', 'razon_social', 'nro_factura',
                'fecha_factura', 'importe_total', 'credito_fiscal', 'cf_iva_contable',
                'descuento', 'cuenta_iva_cf_id', 'cuenta_pago_id', 'almacen_id',
                'forma_pago', 'dias_credito',
            ]),
            'periodo'   => $libro->periodo()->select('id', 'nombre')->first(),
            'almacenes' => Almacen::orderBy('nombre')->get(),   // trae la columna de cuenta, sea cual sea su nombre
            'productos' => Producto::select('id', 'codigo', 'descripcion', 'precio_costo', 'cuenta_activo_id')
                                ->orderBy('descripcion')->get(),
            'cuentas'   => Cuentas::where('permite_movimiento', true)
                                ->select('id', 'codigo', 'nombre')->orderBy('codigo')->get(),
            'cuenta_iva_cf_default' => Cuentas::where('nombre', 'IVA CRÉDITO FISCAL')
                                ->where('permite_movimiento', true)->value('id'),
            'proveedor_cxp' => $proveedorCxp,
        ]);
    }
    public function ingresarInventario(
        Request $request,
        LibroCompra $libro,
        IngresarLibroAInventarioService $svc
    ) {
        $data = $request->validate([
            'almacen_id'           => 'required|exists:almacenes,id',
            'forma_pago'           => 'nullable|in:contado,credito,mixto',
            'dias_credito'         => 'nullable|integer|min:0',
            'cuenta_iva_cf_id'     => 'nullable|exists:cuentas,id',   // nuevo
            'cuenta_pago_id'       => 'nullable|exists:cuentas,id',   // nuevo
            'lineas'               => 'required|array|min:1',
            'lineas.*.producto_id' => 'required|exists:productos,id',
            'lineas.*.cantidad'    => 'required|numeric|gt:0',
            'lineas.*.costo_total' => 'required|numeric|gt:0',
            'contabilizar_ahora' => 'boolean',
        ]);

        try {
            $compra = $svc->ingresar(
                $libro,
                (int) $data['almacen_id'],
                $data['lineas'],
                auth()->id(),
                $data['forma_pago'] ?? null,
                isset($data['dias_credito']) ? (int) $data['dias_credito'] : null,
                $data['cuenta_iva_cf_id'] ?? null,
                $data['cuenta_pago_id'] ?? null
            );
        } catch (\DomainException $e) {
            return back()->withErrors(['inventario' => $e->getMessage()]);
        } catch (\Throwable $e) {
            \Log::error('Error ingresando a inventario libro #' . $libro->id . ': ' . $e->getMessage());
            return back()->withErrors(['inventario' => 'Error al ingresar a inventario: ' . $e->getMessage()]);
        }

        $mensaje = 'Factura ingresada a inventario.';

        if ($request->boolean('contabilizar_ahora')) {
            try {
                app(\App\Services\ContabilizarCompraService::class)->contabilizarCompra($compra);
                $mensaje .= ' Contabilizada correctamente.';
            } catch (\Throwable $e) {
                // El inventario ya quedó ingresado; solo falló el asiento
                return redirect()->route('libro-compras.index', $libro->periodo_id)
                    ->withErrors(['error' => 'Ingresó a inventario, pero no se pudo contabilizar: ' . $e->getMessage()]);
            }
        }

        return redirect()->route('libro-compras.index', $libro->periodo_id)->with('success', $mensaje);
    }
}
