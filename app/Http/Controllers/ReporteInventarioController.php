<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Periodo;
use App\Models\Producto;
use App\Services\InventarioReporteService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reportes de inventario:
 *   1. Kardex por producto            (hoja KARDEX del Excel)
 *   2. Inventario físico valorado     (hoja REPORTE FISICO VALORADO)
 *   3. Inventario físico valorado al corte (hoja INVENTARIO FIS MM, "al 31 de julio")
 *
 * Todos aceptan ?export=csv para descargar y abrir en Excel.
 */
class ReporteInventarioController extends Controller
{
    public function __construct(private InventarioReporteService $reportes) {}

    // ── 1. KARDEX POR PRODUCTO ──────────────────────────────────

    public function kardexProducto(Request $request, $periodoId, Producto $producto)
    {
        $periodo = Periodo::findOrFail($periodoId);

        $f = $request->validate([
            'almacen_id' => 'nullable|exists:almacenes,id',
            'desde'      => 'nullable|date',
            'hasta'      => 'nullable|date|after_or_equal:desde',
        ]);

        // Por defecto: todo el período
        $desde = $f['desde'] ?? optional($periodo->fecha_inicio)->toDateString();
        $hasta = $f['hasta'] ?? null;

        $data = $this->reportes->kardexProducto(
            $producto->id, $desde, $hasta, $f['almacen_id'] ?? null
        );

        if ($request->query('export') === 'csv') {
            return $this->csv(
                "kardex_{$producto->codigo}.csv",
                ['FECHA', 'OPERACIÓN', 'COMPROBANTE', 'ALMACÉN',
                 'ENTRADA F', 'SALIDA F', 'SALDO F', 'COSTO UNIT',
                 'ENTRADA V', 'SALIDA V', 'SALDO V'],
                (function () use ($data) {
                    yield ['', 'Saldo inicial', '', '', '', '', $data['saldo_inicial']['cantidad'],
                           '', '', '', $data['saldo_inicial']['valor']];
                    foreach ($data['lineas'] as $l) {
                        yield [$l['fecha'], $l['operacion'], $l['comprobante'], $l['almacen'],
                               $l['entrada_f'], $l['salida_f'], $l['saldo_f'], $l['costo_prom'],
                               $l['entrada_v'], $l['salida_v'], $l['saldo_v']];
                    }
                })()
            );
        }

        return Inertia::render('Inventarios/Movimientos/KardexProducto', [
            'periodo'   => $periodo,
            'producto'  => $producto->load('unidadMedida:id,codigo'),
            'kardex'    => $data,
            'almacenes' => Almacen::activos()->orderBy('nombre')->get(['id', 'nombre']),
            'productos' => Producto::activos()->orderBy('descripcion')->get(['id', 'codigo', 'descripcion']),
            'filters'   => ['almacen_id' => $f['almacen_id'] ?? null, 'desde' => $desde, 'hasta' => $hasta],
        ]);
    }

    // ── 2 y 3. FÍSICO VALORADO (período) / AL CORTE ─────────────

    public function fisicoValorado(Request $request, $periodoId)
    {
        $periodo = Periodo::findOrFail($periodoId);

        $f = $request->validate([
            'almacen_id'     => 'nullable|exists:almacenes,id',
            'hasta'          => 'nullable|date',          // fecha de corte
            'solo_con_saldo' => 'nullable|boolean',
        ]);

        $data = $this->reportes->fisicoValorado(
            optional($periodo->fecha_inicio)->toDateString(),
            $f['hasta'] ?? optional($periodo->fecha_fin)->toDateString(),
            $f['almacen_id'] ?? null,
            (bool) ($f['solo_con_saldo'] ?? false)
        );

        if ($request->query('export') === 'csv') {
            return $this->csv(
                'inventario_fisico_valorado_' . $data['hasta'] . '.csv',
                ['CODIGO', 'PRODUCTO', 'INV INI FIS', 'ENTRADA F', 'SALIDA F', 'SALDO F',
                 'COSTO PROM', 'INV INI VAL', 'ENTRADA V', 'SALIDA V', 'SALDO V'],
                (function () use ($data) {
                    foreach ($data['filas'] as $r) {
                        yield [$r->codigo, $r->descripcion, $r->inv_ini_f, $r->entrada_f, $r->salida_f,
                               $r->saldo_f, $r->costo_prom, $r->inv_ini_v, $r->entrada_v, $r->salida_v, $r->saldo_v];
                    }
                    $t = $data['totales'];
                    yield ['', 'TOTAL GENERAL', $t['inv_ini_f'], $t['entrada_f'], $t['salida_f'], $t['saldo_f'],
                           '', $t['inv_ini_v'], $t['entrada_v'], $t['salida_v'], $t['saldo_v']];
                })()
            );
        }

        return Inertia::render('Inventarios/Movimientos/FisicoValorado', [
            'periodo'   => $periodo,
            'reporte'   => $data,
            'almacenes' => Almacen::activos()->orderBy('nombre')->get(['id', 'nombre']),
            'filters'   => [
                'almacen_id'     => $f['almacen_id'] ?? null,
                'hasta'          => $data['hasta'],
                'solo_con_saldo' => (bool) ($f['solo_con_saldo'] ?? false),
            ],
        ]);
    }

    // ── CSV (separador ; y BOM para que Excel en español lo abra bien) ──

    private function csv(string $nombre, array $cabecera, iterable $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($cabecera, $filas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $cabecera, ';');
            foreach ($filas as $fila) {
                fputcsv($out, array_map(fn ($v) => $v === null ? '' : $v, $fila), ';');
            }
            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

/*
 |--------------------------------------------------------------------------
 |  RUTAS (agregar en routes/web.php, junto a las de movimientos)
 |--------------------------------------------------------------------------
 |
 |  Route::get('inventario/periodos/{periodo}/reportes/kardex/{producto}',
 |      [ReporteInventarioController::class, 'kardexProducto'])
 |      ->name('inventario.reportes.kardex.producto');
 |
 |  Route::get('inventario/periodos/{periodo}/reportes/fisico-valorado',
 |      [ReporteInventarioController::class, 'fisicoValorado'])
 |      ->name('inventario.reportes.fisico');
 |
 |  - Físico valorado del período:  .../reportes/fisico-valorado
 |  - "FIS MM al 31 de julio":      .../reportes/fisico-valorado?hasta=2026-07-31
 |  - Exportar a Excel:             agregar &export=csv
 */
