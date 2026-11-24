<?php

namespace App\Services;

use App\Models\MovimientoInventario;
use App\Models\StockActual;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reportes de inventario construidos sobre movimientos_inventario.
 *
 *  - kardexProducto()   → kardex de UN producto (igual a la hoja KARDEX del Excel)
 *  - fisicoValorado()   → consolidado por producto (hoja REPORTE FISICO VALORADO)
 *                         y, con $hasta = fecha de corte, el "INVENTARIO FIS MM al 31 de julio".
 *
 * El saldo valorado se arma con costo_total de cada movimiento (que ya guarda el
 * costo según el método del producto: promedio, PEPS o específico), así que
 * funciona igual para los tres métodos.
 */
class InventarioReporteService
{
    public const ENTRADAS = [
        'entrada_compra', 'entrada_devolucion', 'entrada_produccion',
        'entrada_manual', 'entrada_traspaso', 'ajuste_positivo',
    ];

    public const SALIDAS = [
        'salida_venta', 'salida_consumo', 'salida_merma', 'salida_devolucion',
        'salida_produccion', 'salida_traspaso', 'ajuste_negativo',
    ];

    // ══════════════════════════════════════════════════════════════
    //  KARDEX DE UN PRODUCTO
    // ══════════════════════════════════════════════════════════════

    /**
     * @return array{
     *   saldo_inicial: array{cantidad: float, valor: float},
     *   lineas: Collection,
     *   totales: array,
     *   conciliacion: array
     * }
     */
    public function kardexProducto(
        int     $productoId,
        ?string $desde = null,
        ?string $hasta = null,
        ?int    $almacenId = null
    ): array {
        // 1. Saldo inicial = todo lo anterior a "desde"
        $inicial = ['cantidad' => 0.0, 'valor' => 0.0];

        if ($desde) {
            $prev = $this->baseQuery($productoId, $almacenId)
                ->where('fecha', '<', $desde)
                ->selectRaw($this->sumaFirmada('cantidad') . ' as cantidad, '
                          . $this->sumaFirmada('costo_total') . ' as valor')
                ->first();

            $inicial = [
                'cantidad' => (float) ($prev->cantidad ?? 0),
                'valor'    => (float) ($prev->valor ?? 0),
            ];
        }

        // 2. Movimientos del rango, en orden cronológico
        $movs = $this->baseQuery($productoId, $almacenId)
            ->with('almacen:id,nombre')
            ->when($desde, fn ($q) => $q->where('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->where('fecha', '<=', $hasta))
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        // 3. Saldo corrido (físico y valorado)
        $saldoF = $inicial['cantidad'];
        $saldoV = $inicial['valor'];
        $totEntF = $totSalF = $totEntV = $totSalV = 0.0;

        $lineas = $movs->map(function (MovimientoInventario $m) use (
            &$saldoF, &$saldoV, &$totEntF, &$totSalF, &$totEntV, &$totSalV
        ) {
            $esEntrada = in_array($m->tipo, self::ENTRADAS, true);
            $cant  = (float) $m->cantidad;
            $valor = (float) $m->costo_total;

            if ($esEntrada) {
                $saldoF += $cant;  $saldoV += $valor;
                $totEntF += $cant; $totEntV += $valor;
            } else {
                $saldoF -= $cant;  $saldoV -= $valor;
                $totSalF += $cant; $totSalV += $valor;
            }

            return [
                'id'           => $m->id,
                'fecha'        => $m->fecha instanceof \DateTimeInterface
                                    ? $m->fecha->format('Y-m-d') : (string) $m->fecha,
                'tipo'         => $m->tipo,
                'operacion'    => $this->etiquetaTipo($m->tipo),
                'comprobante'  => $this->comprobante($m),
                'almacen'      => $m->almacen->nombre ?? null,
                'motivo'       => $m->motivo,
                // Columnas del Excel: ENTRADA F / SALIDA F / SALDO F
                'entrada_f'    => $esEntrada ? $cant : null,
                'salida_f'     => $esEntrada ? null : $cant,
                'saldo_f'      => round($saldoF, 4),
                // COSTO UNIT (del movimiento) y promedio resultante
                'costo_unit'   => (float) $m->costo_unitario,
                'costo_prom'   => $saldoF > 0 ? round($saldoV / $saldoF, 4) : 0.0,
                // ENTRADA V / SALIDA V / SALDO V
                'entrada_v'    => $esEntrada ? $valor : null,
                'salida_v'     => $esEntrada ? null : $valor,
                'saldo_v'      => round($saldoV, 4),
            ];
        })->values();

        // 4. Conciliación contra stock_actual (detecta descuadres kardex ↔ stock)
        $conciliacion = [];
        if (!$hasta) {
            $stock = StockActual::where('producto_id', $productoId)
                ->when($almacenId, fn ($q) => $q->where('almacen_id', $almacenId))
                ->selectRaw('COALESCE(SUM(cantidad),0) as cantidad, COALESCE(SUM(costo_total),0) as valor')
                ->first();

            $conciliacion = [
                'stock_sistema'  => (float) $stock->cantidad,
                'valor_sistema'  => (float) $stock->valor,
                'dif_cantidad'   => round($saldoF - (float) $stock->cantidad, 4),
                'dif_valor'      => round($saldoV - (float) $stock->valor, 2),
                'cuadra'         => abs($saldoF - (float) $stock->cantidad) < 0.0001,
            ];
        }

        return [
            'saldo_inicial' => $inicial,
            'lineas'        => $lineas,
            'totales'       => [
                'entrada_f' => round($totEntF, 4),
                'salida_f'  => round($totSalF, 4),
                'saldo_f'   => round($saldoF, 4),
                'entrada_v' => round($totEntV, 4),
                'salida_v'  => round($totSalV, 4),
                'saldo_v'   => round($saldoV, 4),
            ],
            'conciliacion'  => $conciliacion,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  FÍSICO VALORADO (consolidado / a fecha de corte)
    // ══════════════════════════════════════════════════════════════

    /**
     * Una fila por producto:
     *   inv_ini_f · entrada_f · salida_f · saldo_f · costo_prom
     *   inv_ini_v · entrada_v · salida_v · saldo_v
     *
     * - $desde  = inicio del período (todo lo anterior es Inventario Inicial).
     *             null → no hay inicial, todo cuenta como entrada.
     * - $hasta  = fecha de corte (ej. 2026-07-31 para "Inventario físico valorado
     *             al 31 de julio"). null → hasta hoy.
     */
    public function fisicoValorado(
        ?string $desde = null,
        ?string $hasta = null,
        ?int    $almacenId = null,
        bool    $soloConSaldo = false
    ): array {
        $hasta ??= now()->toDateString();
        $d   = $desde ? $this->q($desde) : "'1900-01-01'";
        $ent = $this->lista(self::ENTRADAS);
        $sal = $this->lista(self::SALIDAS);

        $rows = DB::table('movimientos_inventario as m')
            ->join('productos as p', 'p.id', '=', 'm.producto_id')
            ->where('m.estado', 'activo')
            ->where('m.fecha', '<=', $hasta)
            ->when($almacenId, fn ($q) => $q->where('m.almacen_id', $almacenId))
            ->groupBy('m.producto_id', 'p.codigo', 'p.descripcion')
            ->orderBy('p.codigo')
            ->selectRaw("
                m.producto_id,
                p.codigo,
                p.descripcion,
                SUM(CASE WHEN m.fecha <  {$d} THEN (CASE WHEN m.tipo IN ({$ent}) THEN m.cantidad ELSE -m.cantidad END) ELSE 0 END) AS inv_ini_f,
                SUM(CASE WHEN m.fecha >= {$d} AND m.tipo IN ({$ent}) THEN m.cantidad ELSE 0 END) AS entrada_f,
                SUM(CASE WHEN m.fecha >= {$d} AND m.tipo IN ({$sal}) THEN m.cantidad ELSE 0 END) AS salida_f,
                SUM(CASE WHEN m.fecha <  {$d} THEN (CASE WHEN m.tipo IN ({$ent}) THEN m.costo_total ELSE -m.costo_total END) ELSE 0 END) AS inv_ini_v,
                SUM(CASE WHEN m.fecha >= {$d} AND m.tipo IN ({$ent}) THEN m.costo_total ELSE 0 END) AS entrada_v,
                SUM(CASE WHEN m.fecha >= {$d} AND m.tipo IN ({$sal}) THEN m.costo_total ELSE 0 END) AS salida_v
            ")
            ->get()
            ->map(function ($r) {
                $r->saldo_f     = round($r->inv_ini_f + $r->entrada_f - $r->salida_f, 4);
                $r->saldo_v     = round($r->inv_ini_v + $r->entrada_v - $r->salida_v, 4);
                $r->costo_prom  = $r->saldo_f > 0 ? round($r->saldo_v / $r->saldo_f, 4) : 0;
                return $r;
            });

        if ($soloConSaldo) {
            $rows = $rows->filter(fn ($r) => $r->saldo_f != 0 || $r->saldo_v != 0)->values();
        }

        $totales = [];
        foreach (['inv_ini_f', 'entrada_f', 'salida_f', 'saldo_f',
                  'inv_ini_v', 'entrada_v', 'salida_v', 'saldo_v'] as $c) {
            $totales[$c] = round($rows->sum($c), 4);
        }

        return [
            'desde'   => $desde,
            'hasta'   => $hasta,
            'filas'   => $rows,
            'totales' => $totales,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  Helpers
    // ══════════════════════════════════════════════════════════════

    private function baseQuery(int $productoId, ?int $almacenId)
    {
        return MovimientoInventario::query()
            ->where('estado', 'activo')
            ->where('producto_id', $productoId)
            ->when($almacenId, fn ($q) => $q->where('almacen_id', $almacenId));
    }

    /** SUM(entradas) - SUM(salidas) de una columna. */
    private function sumaFirmada(string $col): string
    {
        $ent = $this->lista(self::ENTRADAS);
        return "COALESCE(SUM(CASE WHEN tipo IN ({$ent}) THEN {$col} ELSE -{$col} END),0)";
    }

    private function lista(array $valores): string
    {
        return implode(',', array_map(fn ($v) => $this->q($v), $valores));
    }

    private function q(string $v): string
    {
        return DB::getPdo()->quote($v);
    }

    public function etiquetaTipo(string $tipo): string
    {
        return match ($tipo) {
            'entrada_compra'     => 'Compra',
            'entrada_devolucion' => 'Devolución de cliente',
            'entrada_produccion' => 'Entrada por producción',
            'entrada_manual'     => 'Inventario inicial / entrada manual',
            'entrada_traspaso'   => 'Traspaso (entrada)',
            'salida_venta'       => 'Ventas',
            'salida_consumo'     => 'Consumo interno',
            'salida_merma'       => 'Merma / pérdida',
            'salida_devolucion'  => 'Devolución a proveedor',
            'salida_produccion'  => 'Salida por producción',
            'salida_traspaso'    => 'Traspaso (salida)',
            'ajuste_positivo'    => 'Ajuste positivo',
            'ajuste_negativo'    => 'Ajuste negativo',
            default              => $tipo,
        };
    }

    private function comprobante(MovimientoInventario $m): ?string
    {
        if ($m->referencia_tipo && $m->referencia_id) {
            return class_basename($m->referencia_tipo) . ' #' . $m->referencia_id;
        }
        return $m->motivo ?: null;
    }
}
