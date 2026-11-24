<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\Periodo;
use App\Models\StockActual;
use App\Models\ProveedorCliente;
use App\Models\Cuentas;
use App\Models\LibroCompra;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MovimientoInventarioController extends Controller
{
    public function __construct(private InventarioService $inventario) {}

    // ══════════════════════════════════════════════════════════════
    //  ENTRADA MANUAL
    //  Casos: saldo_inicial · entrada_compra (con/sin factura)
    //         entrada_devolucion · entrada_manual (donación, ajuste+)
    // ══════════════════════════════════════════════════════════════

    public function createEntrada($periodoId)
    {
        $periodo = Periodo::findOrFail($periodoId);

        return Inertia::render('Inventarios/Movimientos/EntradaManual', [
            'periodo'     => $periodo,
            'productos'   => Producto::activos()
                                ->conStock()
                                ->with('unidadMedida')
                                ->orderBy('descripcion')
                                ->get(['id','codigo','descripcion',
                                       'precio_costo','aplica_iva',
                                       'unidad_medida_id','metodo_costeo']),
            'almacenes'   => Almacen::activos()
                                ->orderBy('nombre')
                                ->get(['id','codigo','nombre']),
            'proveedores' => ProveedorCliente::activos()
                                ->proveedores()
                                ->orderBy('razon_social')
                                ->get(['id','razon_social','nit']),
            'cuentas'     => Cuentas::visiblePara(auth()->id())
                                ->where('permite_movimiento', true)
                                ->orderBy('codigo')
                                ->get(['id','codigo','nombre']),
            'tipos'       => [
                ['value' => 'entrada_manual',    'label' => 'Saldo inicial / entrada manual',
                 'hint'  => 'Stock de apertura o ajuste positivo sin documento.'],
                ['value' => 'entrada_compra',    'label' => 'Compra de mercadería',
                 'hint'  => 'Compra a proveedor. Puede tener factura SIN o no.'],
                ['value' => 'entrada_devolucion','label' => 'Devolución de cliente',
                 'hint'  => 'Mercadería que un cliente devuelve al almacén.'],
            ],
        ]);
    }

    public function storeEntrada(Request $request)
    {
        $request->validate([
            'periodo_id'       => 'required|exists:periodos,id',
            'tipo'             => 'required|in:entrada_manual,entrada_compra,entrada_devolucion',
            'almacen_id'       => 'required|exists:almacenes,id',
            'fecha'            => 'required|date',
            'motivo'           => 'nullable|string|max:200',
            'observacion'      => 'nullable|string',
            'proveedor_id'     => 'nullable|exists:proveedores_clientes,id',
            // Factura SIN (solo entrada_compra)
            'tiene_factura'    => 'boolean',
            'nro_factura'      => 'nullable|string|max:30',
            'cod_autorizacion' => 'nullable|string|max:100',
            'fecha_factura'    => 'nullable|date',
            'cuenta_pago_id'   => 'nullable|exists:cuentas,id',
            'cuenta_iva_cf_id' => 'nullable|exists:cuentas,id',
            // Líneas de productos
            'lineas'                       => 'required|array|min:1',
            'lineas.*.producto_id'         => 'required|exists:productos,id',
            'lineas.*.cantidad'            => 'required|numeric|min:0.0001',
            'lineas.*.costo_unitario'      => 'required|numeric|min:0',
        ]);

        try {
            return DB::transaction(function () use ($request) {

                $movimientosCreados = [];
                $totalMonto = 0;

                foreach ($request->lineas as $linea) {
                    $mov = $this->inventario->registrarEntrada(
                        tipo:          $request->tipo,
                        productoId:    $linea['producto_id'],
                        almacenId:     $request->almacen_id,
                        cantidad:      (float) $linea['cantidad'],
                        costoUnitario: (float) $linea['costo_unitario'],
                        periodoId:     $request->periodo_id,
                        extra: [
                            'referencia_tipo'      => null,
                            'referencia_id'        => null,
                            'proveedor_cliente_id' => $request->proveedor_id,
                            'fecha'                => $request->fecha,
                            'motivo'               => $request->motivo,
                            'observacion'          => $request->observacion,
                        ]
                    );

                    $movimientosCreados[] = $mov;
                    $totalMonto += $mov->costo_total;
                }

                // Si es compra con factura SIN → crear entrada en libro_compras
                if ($request->tipo === 'entrada_compra'
                    && $request->tiene_factura
                    && $request->nro_factura
                    && $request->proveedor_id)
                {
                    $proveedor = ProveedorCliente::find($request->proveedor_id);
                    $ivaCalc   = round($totalMonto * 13 / 113, 4);

                    LibroCompra::create([
                        'periodo_id'       => $request->periodo_id,
                        'nit_proveedor'    => $proveedor->nit,
                        'razon_social'     => $proveedor->razon_social,
                        'nro_factura'      => $request->nro_factura,
                        'cod_autorizacion' => $request->cod_autorizacion,
                        'fecha_factura'    => $request->fecha_factura ?? $request->fecha,
                        'importe_total'    => $totalMonto,
                        'cf_iva_contable'  => $ivaCalc,
                        'credito_fiscal'   => $ivaCalc,
                        'importe_base_cf'  => $totalMonto - $ivaCalc,
                        'subtotal'         => $totalMonto,
                        'tipo_compra'      => '1',
                        'concepto'         => 'Compra de mercadería (manual)',
                        'cuenta_pago_id'   => $request->cuenta_pago_id,
                        'cuenta_iva_cf_id' => $request->cuenta_iva_cf_id,
                        'importe_debe'     => $totalMonto - $ivaCalc,
                        'importe_haber'    => $totalMonto,
                        'estado'           => 'pendiente',
                        'importado_por'    => auth()->id(),
                    ]);
                }

                $cantLineas = count($movimientosCreados);
                $tipoLabel  = match($request->tipo) {
                    'entrada_compra'     => 'compra',
                    'entrada_devolucion' => 'devolución',
                    default              => 'entrada manual',
                };

                return redirect()
                    ->route('inventario.movimientos.kardex', $request->periodo_id)
                    ->with('success', "Entrada registrada: {$cantLineas} producto(s) ingresados al almacén por {$tipoLabel}.");
            });

        } catch (\Exception $e) {
            Log::error('Error registrando entrada manual: ' . $e->getMessage());
            return back()
                ->withErrors(['error' => 'Error: ' . $e->getMessage()])
                ->withInput();
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  SALIDA MANUAL
    //  Casos: salida_consumo · salida_merma · salida_devolucion
    //  NOTA: salida_venta vendrá del VentaController, no desde aquí
    // ══════════════════════════════════════════════════════════════

    public function createSalida($periodoId)
    {
        $periodo = Periodo::findOrFail($periodoId);

        return Inertia::render('Inventarios/Movimientos/SalidaManual', [
            'periodo'   => $periodo,
            'productos' => Producto::activos()
                              ->conStock()
                              ->with('unidadMedida')
                              ->orderBy('descripcion')
                              ->get(['id','codigo','descripcion',
                                     'precio_costo','unidad_medida_id','metodo_costeo']),
            'almacenes' => Almacen::activos()
                              ->orderBy('nombre')
                              ->get(['id','codigo','nombre']),
            'tipos'     => [
                ['value' => 'salida_consumo',    'label' => 'Consumo interno',
                 'hint'  => 'Uso interno de la empresa: material de oficina, muestras, etc.'],
                ['value' => 'salida_merma',      'label' => 'Merma / pérdida',
                 'hint'  => 'Producto dañado, vencido, roto o perdido.'],
                ['value' => 'salida_devolucion', 'label' => 'Devolución a proveedor',
                 'hint'  => 'Mercadería que se regresa al proveedor.'],
            ],
        ]);
    }

    public function storeSalida(Request $request)
    {
        $request->validate([
            'periodo_id'  => 'required|exists:periodos,id',
            'tipo'        => 'required|in:salida_consumo,salida_merma,salida_devolucion',
            'almacen_id'  => 'required|exists:almacenes,id',
            'fecha'       => 'required|date',
            'motivo'      => 'required|string|max:200',
            'observacion' => 'nullable|string',
            'lineas'               => 'required|array|min:1',
            'lineas.*.producto_id' => 'required|exists:productos,id',
            'lineas.*.cantidad'    => 'required|numeric|min:0.0001',
        ]);

        try {
            return DB::transaction(function () use ($request) {

                // Validar stock antes de procesar cualquier línea
                $erroresStock = [];
                foreach ($request->lineas as $linea) {
                    $producto = Producto::findOrFail($linea['producto_id']);
                    if (!$producto->hayStock($linea['cantidad'], $request->almacen_id)) {
                        $disponible = $producto->stockEnAlmacen($request->almacen_id);
                        $erroresStock[] = "'{$producto->descripcion}': stock insuficiente "
                            . "(disponible: {$disponible}, requerido: {$linea['cantidad']})";
                    }
                }

                if (!empty($erroresStock)) {
                    throw new \Exception(implode(' | ', $erroresStock));
                }

                $movimientosCreados = [];

                foreach ($request->lineas as $linea) {
                    $resultado = $this->inventario->registrarSalida(
                        tipo:       $request->tipo,
                        productoId: $linea['producto_id'],
                        almacenId:  $request->almacen_id,
                        cantidad:   (float) $linea['cantidad'],
                        periodoId:  $request->periodo_id,
                        extra: [
                            'fecha'       => $request->fecha,
                            'motivo'      => $request->motivo,
                            'observacion' => $request->observacion,
                        ]
                    );
                    $movimientosCreados[] = $resultado['movimiento'];
                }

                $cantLineas = count($movimientosCreados);
                $tipoLabel  = match($request->tipo) {
                    'salida_consumo'    => 'consumo interno',
                    'salida_merma'      => 'merma/pérdida',
                    'salida_devolucion' => 'devolución a proveedor',
                    default             => 'salida',
                };

                return redirect()
                    ->route('movimientos.kardex', $request->periodo_id)
                    ->with('success', "{$cantLineas} producto(s) dados de baja del almacén por {$tipoLabel}.");
            });

        } catch (\Exception $e) {
            Log::error('Error registrando salida manual: ' . $e->getMessage());
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  KARDEX GENERAL (todos los movimientos del período)
    // ══════════════════════════════════════════════════════════════

    public function kardex(Request $request, $periodoId)
    {
        $periodo = Periodo::findOrFail($periodoId);

        // 1. La consulta ahora ordena ASCENDENTE (del más antiguo al más nuevo)
        $query = MovimientoInventario::with([
                'producto:id,codigo,descripcion,unidad_medida_id',
                'producto.unidadMedida:id,codigo',
                'almacen:id,nombre',
                'creadoPor:id,name',
            ])
            ->activos()
            ->enPeriodo($periodoId);

        // Filtros (se mantienen igual)
        if ($request->filled('producto_id')) $query->deProducto($request->producto_id);
        if ($request->filled('almacen_id'))  $query->deAlmacen($request->almacen_id);
        if ($request->filled('tipo'))        $query->where('tipo', $request->tipo);
        if ($request->filled('desde'))       $query->where('fecha', '>=', $request->desde);
        if ($request->filled('hasta'))       $query->where('fecha', '<=', $request->hasta);

        // 2. Ordenamos por fecha ASCENDENTE (como en tu Excel)
        $movimientos = $query->orderBy('fecha', 'asc')
                            ->orderBy('id', 'asc')
                            ->paginate(50) // Aumentamos a 50 para ver más filas
                            ->withQueryString();

        // 3. Resumen del período (igual que antes)
        $resumen = MovimientoInventario::activos()
            ->enPeriodo($periodoId)
            ->selectRaw("
                SUM(CASE WHEN tipo LIKE 'entrada%' OR tipo = 'ajuste_positivo'
                    THEN costo_total ELSE 0 END) as total_entradas,
                SUM(CASE WHEN tipo LIKE 'salida%' OR tipo = 'ajuste_negativo'
                    THEN costo_total ELSE 0 END) as total_salidas,
                COUNT(*) as total_movimientos
            ")
            ->first();

        return Inertia::render('Inventarios/Movimientos/Kardex', [
            'periodo'      => $periodo,
            'movimientos'  => $movimientos,
            'resumen'      => $resumen,
            'productos'    => Producto::activos()->orderBy('descripcion')->get(['id','codigo','descripcion']),
            'almacenes'    => Almacen::activos()->get(['id','nombre']),
            'tiposOpciones'=> $this->tiposOpciones(),
            'filters'      => $request->only(['producto_id','almacen_id','tipo','desde','hasta']),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  API: stock disponible de un producto en un almacén
    //  (llamada AJAX desde los formularios para pre-llenar costo)
    // ══════════════════════════════════════════════════════════════

    public function stockProducto(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'almacen_id'  => 'required|exists:almacenes,id',
        ]);

        $stock = StockActual::where('producto_id', $request->producto_id)
                            ->where('almacen_id', $request->almacen_id)
                            ->first();

        $producto = Producto::find($request->producto_id);

        return response()->json([
            'cantidad'       => $stock ? (float) $stock->cantidad : 0,
            'costo_promedio' => $stock ? (float) $stock->costo_promedio : (float) $producto->precio_costo,
            'costo_total'    => $stock ? (float) $stock->costo_total : 0,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  ANULAR un movimiento
    // ══════════════════════════════════════════════════════════════

    public function anular(Request $request, MovimientoInventario $movimiento)
    {
        $request->validate(['motivo' => 'required|string|max:200']);

        if (!$movimiento->estaActivo()) {
            return back()->withErrors(['error' => 'Este movimiento ya fue anulado.']);
        }

        // No permitir anular movimientos generados por ventas (deben anularse desde la venta)
        if (in_array($movimiento->tipo, ['salida_venta', 'entrada_devolucion'])
            && $movimiento->referencia_tipo === 'ventas') {
            return back()->withErrors(['error' => 'Este movimiento fue generado por una venta. Anula la venta para revertirlo.']);
        }

        try {
            DB::transaction(function () use ($movimiento, $request) {
                // Revertir el efecto en stock_actual
                $stock = StockActual::obtenerOCrear(
                    $movimiento->producto_id,
                    $movimiento->almacen_id
                );

                if ($movimiento->esEntrada()) {
                    // Era una entrada → revertir bajando stock
                    $stock->aplicarSalida((float) $movimiento->cantidad);
                } else {
                    // Era una salida → revertir subiendo stock al costo original
                    $stock->aplicarEntrada(
                        (float) $movimiento->cantidad,
                        (float) $movimiento->costo_unitario
                    );
                }

                $movimiento->anular($request->motivo, auth()->id());
            });

            return back()->with('success', 'Movimiento anulado correctamente. Stock revertido.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al anular: ' . $e->getMessage()]);
        }
    }

    // ── Helper privado ───────────────────────────────────────────
    private function tiposOpciones(): array
    {
        return [
            ['value' => '',                    'label' => 'Todos los tipos'],
            ['value' => 'entrada_compra',      'label' => 'Entrada por compra'],
            ['value' => 'entrada_manual',      'label' => 'Entrada manual / saldo inicial'],
            ['value' => 'entrada_devolucion',  'label' => 'Devolución de cliente'],
            ['value' => 'entrada_produccion',  'label' => 'Entrada por producción'],
            ['value' => 'salida_venta',        'label' => 'Salida por venta'],
            ['value' => 'salida_consumo',      'label' => 'Consumo interno'],
            ['value' => 'salida_merma',        'label' => 'Merma / pérdida'],
            ['value' => 'salida_devolucion',   'label' => 'Devolución a proveedor'],
            ['value' => 'ajuste_positivo',     'label' => 'Ajuste positivo'],
            ['value' => 'ajuste_negativo',     'label' => 'Ajuste negativo'],
            ['value' => 'entrada_traspaso',    'label' => 'Traspaso (entrada)'],
            ['value' => 'salida_traspaso',     'label' => 'Traspaso (salida)'],
        ];
    }
    public function kardexProducto(Request $request, $periodoId, Producto $producto)
    {
        $periodo = Periodo::findOrFail($periodoId);
        $almacenId = $request->almacen_id; // opcional

        $movs = MovimientoInventario::activos()
            ->enPeriodo($periodoId)
            ->deProducto($producto->id)
            ->when($almacenId, fn($q) => $q->deAlmacen($almacenId))
            ->orderBy('fecha')->orderBy('id')
            ->get();

        // Saldo inicial del período = stock actual - movimientos del período
        $stock = StockActual::where('producto_id', $producto->id)
            ->when($almacenId, fn($q) => $q->where('almacen_id', $almacenId))
            ->get();
        $saldoIniF = (float) $stock->sum('cantidad')
            - $movs->where('esEntrada', true)->sum('cantidad')
            + $movs->sum(fn($m) => $m->esEntrada() ? 0 : $m->cantidad);
        $saldoIniV = (float) $stock->sum('costo_total')
            - $movs->sum(fn($m) => $m->esEntrada() ? $m->costo_total : 0)
            + $movs->sum(fn($m) => $m->esEntrada() ? 0 : $m->costo_total);

        // Totales del período
        $totEntF = $movs->where(fn($m) => $m->esEntrada())->sum('cantidad');
        $totSalF = $movs->where(fn($m) => !$m->esEntrada())->sum('cantidad');
        $totEntV = $movs->where(fn($m) => $m->esEntrada())->sum('costo_total');
        $totSalV = $movs->where(fn($m) => !$m->esEntrada())->sum('costo_total'); // = CMV

        return Inertia::render('Inventarios/Movimientos/KardexProducto', [
            'periodo'  => $periodo,
            'producto' => $producto,
            'lineas'   => $movs->map(fn($m) => [
                'fecha'       => $m->fecha,
                'operacion'   => $m->tipo,
                'comprobante' => $m->comprobante,
                'entrada_f'   => $m->esEntrada() ? (float)$m->cantidad : 0,
                'salida_f'    => $m->esEntrada() ? 0 : (float)$m->cantidad,
                'saldo_f'     => (float)$m->stock_resultante,
                'costo_unit'  => (float)$m->costo_unitario,
                'entrada_v'   => $m->esEntrada() ? (float)$m->costo_total : 0,
                'salida_v'    => $m->esEntrada() ? 0 : (float)$m->costo_total,
                'saldo_v'     => (float)$m->saldo_valorado,   // ← lo nuevo
                'anulado'     => !$m->estaActivo(),
            ]),
            'saldoInicial' => ['fisico' => $saldoIniF, 'valor' => round($saldoIniV, 2)],
            'totales'      => ['ent_f' => $totEntF, 'sal_f' => $totSalF,
                            'ent_v' => round($totEntV,2), 'sal_v' => round($totSalV,2)],
        ]);
    }
    public function fisicoValorado($periodoId)
    {
        $periodo = Periodo::findOrFail($periodoId);

        $filas = StockActual::with('producto:id,codigo,descripcion')
            ->whereHas('producto', fn($q) => $q->where('activo', true))
            ->get()
            ->map(fn($s) => [
                'codigo'     => $s->producto->codigo,
                'producto'   => $s->producto->descripcion,
                'saldo_fis'  => (float) $s->cantidad,
                'costo_unit' => (float) $s->costo_promedio,
                'saldo_val'  => round((float) $s->costo_total, 2),
            ])
            ->filter(fn($f) => $f['saldo_fis'] != 0)
            ->sortByDesc('saldo_val')
            ->values();

        // Clasificación ABC
        $total = $filas->sum('saldo_val') ?: 1;
        $acum = 0;
        $filas = $filas->map(function ($f) use (&$acum, $total) {
            $f['pct']   = round($f['saldo_val'] / $total * 100, 2);
            $acum    += $f['pct'];
            $f['pct_acum'] = round($acum, 2);
            $f['abc'] = $acum <= 80 ? 'A' : ($acum <= 95 ? 'B' : 'C');
            return $f;
        });

        return Inertia::render('Inventarios/Reportes/FisicoValorado', [
            'periodo' => $periodo,
            'filas'   => $filas,
            'total'   => round($total, 2),
        ]);
    }
}
