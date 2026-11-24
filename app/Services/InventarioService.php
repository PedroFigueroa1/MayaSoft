<?php
namespace App\Services;

use App\Models\Producto;
use App\Models\Cuentas;
use App\Models\Almacen;
use App\Models\StockActual;
use App\Models\LotePeps;
use App\Models\MovimientoInventario;
use App\Models\Asiento;
use App\Models\DetalleAsiento;
use App\Helpers\AsientoHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventarioService
{
    // ══════════════════════════════════════════════════════════════
    //  ENTRADA — suma stock
    // ══════════════════════════════════════════════════════════════

    /**
     * Registra una entrada de inventario.
     * Llamar siempre desde dentro de DB::transaction() del caller.
     *
     * @param  string $tipo        Tipo de movimiento (entrada_compra, entrada_manual, etc.)
     * @param  int    $productoId
     * @param  int    $almacenId
     * @param  float  $cantidad
     * @param  float  $costoUnitario
     * @param  int    $periodoId
     * @param  array  $extra       ['referencia_tipo', 'referencia_id', 'motivo', 'fecha', 'proveedor_cliente_id']
     * @return MovimientoInventario
     */
    public function registrarEntrada(
        string $tipo, int $productoId, int $almacenId, float $cantidad,
        float $costoUnitario, int $periodoId, array $extra = []
    ): MovimientoInventario {
        $producto = Producto::findOrFail($productoId);
        $stock    = StockActual::obtenerOCrear($productoId, $almacenId);

        $stock->aplicarEntrada($cantidad, $costoUnitario);

        if ($producto->usaPeps()) {
            LotePeps::create([
                'producto_id'         => $productoId,
                'almacen_id'          => $almacenId,
                'cantidad_inicial'    => $cantidad,
                'cantidad_disponible' => $cantidad,
                'costo_unitario'      => round($costoUnitario, 4),
                'fecha_entrada'       => $extra['fecha'] ?? today(),
            ]);
        }

        return MovimientoInventario::create([
            'tipo'                 => $tipo,
            'producto_id'          => $productoId,
            'almacen_id'           => $almacenId,
            'cantidad'             => $cantidad,
            'costo_unitario'       => round($costoUnitario, 4),
            'costo_total'          => round($cantidad * $costoUnitario, 4),
            'stock_resultante'     => $stock->cantidad,
            'saldo_valorado'       => $stock->costo_total,
            'periodo_id'           => $periodoId,
            'fecha'                => $extra['fecha'] ?? today(),
            'referencia_tipo'      => $extra['referencia_tipo'] ?? null,
            'referencia_id'        => $extra['referencia_id'] ?? null,
            'motivo'               => $extra['motivo'] ?? null,
            'proveedor_cliente_id' => $extra['proveedor_cliente_id'] ?? null,
            'estado'               => 'activo',
            'creado_por'           => Auth::id(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  SALIDA — resta stock
    // ══════════════════════════════════════════════════════════════

    /**
     * Registra una salida de inventario.
     * Calcula el costo automáticamente según el método del producto.
     *
     * @return array ['movimiento' => MovimientoInventario, 'costo_unitario' => float, 'costo_total' => float]
     */
    public function registrarSalida(
        string $tipo,
        int    $productoId,
        int    $almacenId,
        float  $cantidad,
        int    $periodoId,
        array  $extra = []
    ): array {
        $producto = Producto::findOrFail($productoId);
        $stock    = StockActual::obtenerOCrear($productoId, $almacenId);

        // Validar stock suficiente
        if ($producto->controla_stock && (float) $stock->cantidad < $cantidad) {
            throw new \Exception(
                "Stock insuficiente para '{$producto->descripcion}'. " .
                "Disponible: {$stock->cantidad} | Requerido: {$cantidad}"
            );
        }

        // Calcular costo según método
        [$costoUnitario, $loteId] = $this->calcularCostoSalida($producto, $almacenId, $cantidad);
        $costoTotal = round($cantidad * $costoUnitario, 4);

        $metodo = strtoupper((string) $producto->metodo_costeo);
        $stock->aplicarSalida($cantidad, in_array($metodo, ['PEPS', 'ESP']) ? $costoTotal : null);

        // Actualizar stock_actual
        $stock->aplicarSalida($cantidad);

        // Registrar movimiento kardex
        $movimiento = MovimientoInventario::create([
            'tipo'                => $tipo,
            'producto_id'         => $productoId,
            'almacen_id'          => $almacenId,
            'cantidad'            => $cantidad,
            'costo_unitario'      => $costoUnitario,
            'costo_total'         => $costoTotal,
            'precio_venta'        => $extra['precio_venta'] ?? 0,
            'stock_resultante'    => $stock->cantidad,
            'saldo_valorado'   => $stock->costo_total,
            'lote_id'             => $loteId,
            'periodo_id'          => $periodoId,
            'fecha'               => $extra['fecha'] ?? today(),
            'referencia_tipo'     => $extra['referencia_tipo'] ?? null,
            'referencia_id'       => $extra['referencia_id'] ?? null,
            'motivo'              => $extra['motivo'] ?? null,
            'proveedor_cliente_id'=> $extra['proveedor_cliente_id'] ?? null,
            'estado'              => 'activo',
            'creado_por'          => Auth::id(),
        ]);

        return [
            'movimiento'     => $movimiento,
            'costo_unitario' => $costoUnitario,
            'costo_total'    => $costoTotal,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  CÁLCULO DE COSTO SEGÚN MÉTODO
    // ══════════════════════════════════════════════════════════════

    /**
     * Calcula el costo unitario de una salida.
     * Retorna [costo_unitario, lote_id|null]
     */
    private function calcularCostoSalida(Producto $producto, int $almacenId, float $cantidad): array
    {
        $metodo = strtoupper((string) $producto->metodo_costeo);

        $stock    = StockActual::where('producto_id', $producto->id)
                            ->where('almacen_id', $almacenId)->first();
        $promedio = $stock ? (float) $stock->costo_promedio : 0.0;

        return match ($metodo) {
            'PEPS'  => $this->calcularCostoPeps($producto->id, $almacenId, $cantidad),
            // ESP: costo definido en el producto; si está en 0, usa el promedio para no regalar utilidad
            'ESP'   => [(float) $producto->precio_costo > 0 ? (float) $producto->precio_costo : $promedio, null],
            // PROM
            default => [$promedio > 0 ? $promedio : (float) $producto->precio_costo, null],
        };
    }

    /**
     * PEPS: consume lotes en orden de entrada (más antiguo primero).
     * Si la venta necesita más de un lote, calcula el promedio ponderado del costo.
     * Retorna el costo promedio ponderado y el lote principal.
     */
    private function calcularCostoPeps(int $productoId, int $almacenId, float $cantidad): array
    {
        $lotes = LotePeps::where('producto_id', $productoId)
                         ->where('almacen_id', $almacenId)
                         ->disponibles()
                         ->ordenPeps()
                         ->get();

        $restante       = $cantidad;
        $costoTotal     = 0.0;
        $primerLoteId   = null;

        foreach ($lotes as $lote) {
            if ($restante <= 0) break;

            $consumido   = $lote->consumir($restante);
            $costoTotal += $consumido * (float) $lote->costo_unitario;
            $restante   -= $consumido;

            if ($primerLoteId === null) $primerLoteId = $lote->id;
        }

        if ($restante > 0) {
            throw new \Exception("Stock PEPS insuficiente. Falta: $restante unidades.");
        }

        $costoUnitario = $cantidad > 0 ? round($costoTotal / $cantidad, 4) : 0.0;
        return [$costoUnitario, $primerLoteId];
    }

    // ══════════════════════════════════════════════════════════════
    //  TRASPASO
    // ══════════════════════════════════════════════════════════════

    /**
     * Registra un traspaso entre dos almacenes.
     * Crea dos movimientos: salida_traspaso + entrada_traspaso.
     */
    public function registrarTraspaso(
        int   $productoId,
        int   $almacenOrigenId,
        int   $almacenDestinoId,
        float $cantidad,
        int   $periodoId,
        array $extra = []
    ): array {
        // Salida del origen
        $resultSalida = $this->registrarSalida(
            'salida_traspaso',
            $productoId,
            $almacenOrigenId,
            $cantidad,
            $periodoId,
            array_merge($extra, ['referencia_tipo' => $extra['referencia_tipo'] ?? null])
        );

        // Entrada al destino (al mismo costo que salió)
        $movEntrada = $this->registrarEntrada(
            'entrada_traspaso',
            $productoId,
            $almacenDestinoId,
            $cantidad,
            $resultSalida['costo_unitario'],
            $periodoId,
            $extra
        );

        return [
            'movimiento_salida'  => $resultSalida['movimiento'],
            'movimiento_entrada' => $movEntrada,
            'costo_unitario'     => $resultSalida['costo_unitario'],
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  ASIENTOS CONTABLES AUTOMÁTICOS
    // ══════════════════════════════════════════════════════════════

    /**
     * Genera el asiento contable de una venta (ingreso + costo de venta).
     * Este asiento tiene CUATRO líneas:
     *   DEBE:  Caja/CxC            = total venta
     *   HABER: Ingresos/Ventas     = total - iva_df
     *   HABER: IVA Débito Fiscal   = iva_df
     *   DEBE:  Costo de ventas     = costo kardex
     *   HABER: Almacén/Mercaderías = costo kardex
     */
    public function generarAsientoVenta(\App\Models\Venta $venta): Asiento
    {
        $numeroAsiento = AsientoHelper::generarNumeroAsiento($venta->periodo_id, 'ING');

        $asiento = Asiento::create([
            'periodo_id'     => $venta->periodo_id,
            'fecha'          => $venta->fecha,
            'numero_asiento' => $numeroAsiento,
            'tipo'           => 'ING',
            'glosa'          => 'Venta ' . ($venta->nro_factura ?? $venta->nro_venta) . ' - ' . $venta->nombreCliente(),
            'comprobante'    => $venta->nro_factura ?? $venta->nro_venta,
            'creado_por'     => Auth::id(),
        ]);

        $lineas        = [];
        $totalBase     = 0.0;
        $totalIva      = 0.0;

        // ── 1. Detalles: base sin IVA, CMV y salida de inventario ──
        foreach ($venta->detalles as $det) {
            $prod  = $det->producto;
            $bruto = (float) $det->cantidad * (float) $det->precio_unitario - (float) ($det->descuento ?? 0);

            if ($prod->aplica_iva) {
                $base = round($bruto * 0.87, 4);
                $iva  = round($bruto * 0.13, 4);
            } else {
                $base = round($bruto, 4);
                $iva  = 0.0;
            }

            $totalBase += $base;
            $totalIva  += $iva;

            if ($prod->cuenta_costo_venta_id && (float) $det->costo_total > 0) {
                $lineas[] = ['cuenta_id' => $prod->cuenta_costo_venta_id, 'debe' => (float) $det->costo_total, 'haber' => 0, 'descripcion' => 'CMV ' . $prod->descripcion];
            }
            if ($prod->cuenta_activo_id && (float) $det->costo_total > 0) {
                $lineas[] = ['cuenta_id' => $prod->cuenta_activo_id, 'debe' => 0, 'haber' => (float) $det->costo_total, 'descripcion' => 'Salida ' . $prod->descripcion];
            }
            if ($prod->cuenta_ingreso_id && $base > 0) {
                $lineas[] = ['cuenta_id' => $prod->cuenta_ingreso_id, 'debe' => 0, 'haber' => $base, 'descripcion' => 'Venta ' . $prod->descripcion];
            }
        }

        $totalVenta = round($totalBase + $totalIva, 2);

        // 🔥 IT 3% — igual que en LibroVentasController
        $itMonto = round($totalVenta * 0.03, 2);

        // ── 2. DEBE: Cobro ──
        $pagosConCuenta = $venta->pagos->filter(fn($p) => $p->cuenta_id && (float) $p->monto > 0);

        if ($pagosConCuenta->count() > 0) {
            foreach ($pagosConCuenta as $pago) {
                $lineas[] = ['cuenta_id' => $pago->cuenta_id, 'debe' => (float) $pago->monto, 'haber' => 0, 'descripcion' => 'Cobro ' . $pago->medio];
            }
            $pagado = $pagosConCuenta->sum('monto');
            if ($totalVenta > $pagado && $venta->cliente?->cuenta_por_cobrar_id) {
                $lineas[] = ['cuenta_id' => $venta->cliente->cuenta_por_cobrar_id, 'debe' => round($totalVenta - $pagado, 2), 'haber' => 0, 'descripcion' => 'CxC venta'];
            }
        } elseif ($venta->cuenta_cobro_id) {
            $lineas[] = ['cuenta_id' => $venta->cuenta_cobro_id, 'debe' => $totalVenta, 'haber' => 0, 'descripcion' => 'Cobro venta'];
        }

        // ── 3. DEBE: IT (3%) — gasto 🔥 ──
        if ($itMonto > 0) {
            $lineas[] = [
                'cuenta_id'   => $this->getCuentaItGasto()->id,
                'debe'        => $itMonto,
                'haber'       => 0,
                'descripcion' => 'Impuesto a las Transacciones (3%)',
            ];
        }

        // ── 4. HABER: IVA DF ──
        if ($venta->cuenta_iva_df_id && $totalIva > 0) {
            $lineas[] = ['cuenta_id' => $venta->cuenta_iva_df_id, 'debe' => 0, 'haber' => $totalIva, 'descripcion' => 'DF IVA'];
        }

        // ── 5. HABER: IT por Pagar 🔥 ──
        if ($itMonto > 0) {
            $lineas[] = [
                'cuenta_id'   => $this->getCuentaItPorPagar()->id,
                'debe'        => 0,
                'haber'       => $itMonto,
                'descripcion' => 'Impuesto a las Transacciones por Pagar',
            ];
        }

        $this->crearLineasAsiento($asiento->id, $lineas);
        $this->validarBalance($asiento);

        $venta->update([
            'subtotal'          => $totalBase,
            'iva_df'            => $totalIva,
            'total'             => $totalVenta,
            'it'                => $itMonto,          // 🔥 si tu tabla ventas tiene columna `it`
            'asiento_id'        => $asiento->id,
            'estado_contable'   => 'contabilizada',
            'contabilizado_at'  => now(),
            'contabilizado_por' => Auth::id(),
        ]);

        return $asiento;
    }

    /**
     * Genera asiento de entrada por compra.
     *   DEBE:  Almacén/Mercaderías = importe sin IVA
     *   DEBE:  IVA CF              = iva_cf
     *   HABER: Caja/Banco/CxP     = total
     */
    public function generarAsientoCompra(\App\Models\Compra $compra): Asiento
    {
        $numeroAsiento = AsientoHelper::generarNumeroAsiento($compra->periodo_id, 'EGR');

        $asiento = Asiento::create([
            'periodo_id'     => $compra->periodo_id,
            'fecha'          => $compra->fecha_recepcion,
            'numero_asiento' => $numeroAsiento,
            'tipo'           => 'EGR',
            'glosa'          => 'Compra ' . ($compra->nro_factura ?? $compra->nro_compra) . ' - ' . $compra->proveedor->razon_social,
            'comprobante'    => $compra->nro_factura ?? $compra->nro_compra,
            'creado_por'     => Auth::id(),
        ]);

        $lineas = [];
        $totalBase = 0;
        $totalIva  = 0;

        foreach ($compra->detalles as $det) {
            $prod  = $det->producto;
            $bruto = (float) $det->cantidad * (float) $det->costo_unitario - (float) $det->descuento;

            // 🔑 Si el producto aplica IVA, la mercadería se registra SIN IVA
            if ($prod->aplica_iva) {
                $base = round($bruto * 0.87, 4);   // 45 → 39.82
                $iva  = round($bruto * 0.13, 4);  // 5.18
            } else {
                $base = round($bruto, 4);
                $iva  = 0;
            }

            $totalBase += $base;
            $totalIva  += $iva;

            if (!$prod->cuenta_activo_id) {
                throw new \Exception("El producto '{$prod->descripcion}' no tiene cuenta de inventario asignada.");
            }

            $lineas[] = [
                'cuenta_id'   => $prod->cuenta_activo_id,
                'debe'        => $base,
                'haber'       => 0,
                'descripcion' => 'Entrada ' . $prod->descripcion,
            ];

            if ($iva > 0 && $compra->cuenta_iva_cf_id) {
                $lineas[] = [
                    'cuenta_id'   => $compra->cuenta_iva_cf_id,
                    'debe'        => $iva,
                    'haber'       => 0,
                    'descripcion' => 'CF IVA ' . $prod->descripcion,
                ];
            }
        }

        // HABER: total real pagado (base + IVA)
        $totalPagar = round($totalBase + $totalIva, 2);
        $cuentaHaber = $compra->forma_pago === 'credito' && $compra->proveedor?->cuenta_por_pagar_id
            ? $compra->proveedor->cuenta_por_pagar_id
            : $compra->cuenta_pago_id;

        $lineas[] = [
            'cuenta_id'   => $cuentaHaber,
            'debe'        => 0,
            'haber'       => $totalPagar,
            'descripcion' => $compra->forma_pago === 'credito' ? 'CxP compra' : 'Pago compra',
        ];

        $this->crearLineasAsiento($asiento->id, $lineas);
        $this->validarBalance($asiento);

        $compra->update([
            'subtotal' => $totalBase,
            'iva_cf'   => $totalIva,
            'total'    => $totalPagar,
            'asiento_id' => $asiento->id,
            'estado'   => 'contabilizada',
        ]);

        return $asiento;
    }

    /**
     * Genera asiento consolidado de cierre de caja.
     * Un solo asiento para todas las ventas del día.
     */
    public function generarAsientoCierreCaja(\App\Models\CierreCaja $cierre): Asiento
    {
        $ventas = $cierre->ventasPendientes()->with('detalles.producto', 'pagos')->get();

        $numeroAsiento = AsientoHelper::generarNumeroAsiento($cierre->periodo_id, 'ING');

        $asiento = Asiento::create([
            'periodo_id'     => $cierre->periodo_id,
            'fecha'          => $cierre->fecha,
            'numero_asiento' => $numeroAsiento,
            'tipo'           => 'ING',
            'glosa'          => 'Cierre de caja ' . $cierre->fecha->format('d/m/Y') . ' - ' . $cierre->almacen->nombre . ' (' . $ventas->count() . ' ventas)',
            'comprobante'    => null,
            'creado_por'     => Auth::id(),
        ]);

        // Agrupar por cuenta
        $acumulado = [];
        $fn = function(int $cuentaId, float $debe, float $haber, string $desc) use (&$acumulado) {
            $k = $cuentaId . '-' . $desc;
            if (!isset($acumulado[$k])) $acumulado[$k] = ['cuenta_id' => $cuentaId, 'debe' => 0, 'haber' => 0, 'descripcion' => $desc];
            $acumulado[$k]['debe']  += $debe;
            $acumulado[$k]['haber'] += $haber;
        };

        foreach ($ventas as $venta) {
            // Pagos (DEBE)
            foreach ($venta->pagos as $pago) {
                if ($pago->cuenta_id) $fn($pago->cuenta_id, (float) $pago->monto, 0, $pago->medioLabel());
            }
            // IVA DF (HABER)
            if ($venta->cuenta_iva_df_id && (float) $venta->iva_df > 0) {
                $fn($venta->cuenta_iva_df_id, 0, (float) $venta->iva_df, 'DF IVA');
            }
            foreach ($venta->detalles as $det) {
                $prod = $det->producto;
                if ($prod->cuenta_ingreso_id)       $fn($prod->cuenta_ingreso_id, 0, (float) $det->subtotal, 'Ingresos ventas');
                if ($prod->cuenta_costo_venta_id)   $fn($prod->cuenta_costo_venta_id, (float) $det->costo_total, 0, 'CMV');
                if ($prod->cuenta_activo_id)        $fn($prod->cuenta_activo_id, 0, (float) $det->costo_total, 'Salida inventario');
            }

            $venta->update(['estado_contable' => 'contabilizada', 'contabilizado_at' => now(), 'contabilizado_por' => Auth::id()]);
        }

        $this->crearLineasAsiento($asiento->id, array_values($acumulado));
        $this->validarBalance($asiento);

        $cierre->update(['asiento_id' => $asiento->id, 'estado' => 'contabilizado', 'contabilizado_at' => now(), 'contabilizado_por' => Auth::id()]);

        return $asiento;
    }

    // ── Helpers privados ─────────────────────────────────────────

    private function crearLineasAsiento(int $asientoId, array $lineas): void
    {
        foreach ($lineas as $l) {
            if ((float)($l['debe'] ?? 0) == 0 && (float)($l['haber'] ?? 0) == 0) continue;
            DetalleAsiento::create([
                'asiento_id'  => $asientoId,
                'cuenta_id'   => $l['cuenta_id'],
                'debe'        => round((float)($l['debe'] ?? 0), 4),
                'haber'       => round((float)($l['haber'] ?? 0), 4),
                'descripcion' => $l['descripcion'] ?? null,
                'deducible'   => $l['deducible'] ?? false,
            ]);
        }
    }

    private function validarBalance(Asiento $asiento): void
    {
        $asiento->load('detalles');
        $debe  = $asiento->detalles->sum('debe');
        $haber = $asiento->detalles->sum('haber');
        if (round($debe, 2) !== round($haber, 2)) {
            throw new \Exception("Asiento desbalanceado {$asiento->numero_asiento}. Debe: $debe | Haber: $haber");
        }
    }
    private function getCuentaItGasto(): Cuentas
    {
        $cuenta = Cuentas::where('nombre', 'Impuesto a las Transacciones')
            ->where('permite_movimiento', true)
            ->first();

        if (!$cuenta) {
            throw new \Exception('No hay una cuenta de gasto "Impuesto a las Transacciones" configurada.');
        }
        return $cuenta;
    }

    private function getCuentaItPorPagar(): Cuentas
    {
        $cuenta = Cuentas::where('nombre', 'Impuesto a las Transacciones por Pagar')
            ->where('permite_movimiento', true)
            ->first();

        if (!$cuenta) {
            throw new \Exception('No hay una cuenta de pasivo "Impuesto a las Transacciones por Pagar" configurada.');
        }
        return $cuenta;
    }
    public function anularSalida(MovimientoInventario $mov, string $motivo): void
    {
        if (!$mov->estaActivo()) return;

        $producto = Producto::findOrFail($mov->producto_id);
        $stock    = StockActual::obtenerOCrear($mov->producto_id, $mov->almacen_id);

        $stock->revertirSalida((float) $mov->cantidad, (float) $mov->costo_unitario);

        if (strtoupper((string) $producto->metodo_costeo) === 'PEPS') {
            LotePeps::create([
                'producto_id'         => $mov->producto_id,
                'almacen_id'          => $mov->almacen_id,
                'cantidad_inicial'    => (float) $mov->cantidad,
                'cantidad_disponible' => (float) $mov->cantidad,
                'costo_unitario'      => (float) $mov->costo_unitario,
                'fecha_entrada'       => $mov->fecha,   // conserva su posición en la cola
            ]);
        }

        $mov->anular($motivo, Auth::id());
    }

}
