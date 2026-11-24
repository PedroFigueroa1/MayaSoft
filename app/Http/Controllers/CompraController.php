<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Producto;
use App\Models\ProveedorCliente;
use App\Models\Almacen;
use App\Models\Periodo;
use App\Models\Cuentas;
use App\Models\LibroCompra;
use App\Services\InventarioService;
use App\Http\Requests\Inventario\CompraRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompraController extends Controller
{
    public function __construct(private InventarioService $inventario) {}

    // ── INDEX ────────────────────────────────────────────────────
    public function index(Request $request, $periodoId)
    {
        $periodo = Periodo::findOrFail($periodoId);

        $query = Compra::with([
                'proveedor:id,razon_social,nit',
                'almacen:id,nombre',
                // 👇 preview: solo 3 detalles con producto + unidad
                'detalles' => function ($q) {
                    $q->select('id', 'compra_id', 'producto_id', 'cantidad', 'costo_unitario')
                      ->with([
                          'producto:id,codigo,descripcion,unidad_medida_id',
                          'producto.unidadMedida:id,codigo,nombre',
                      ])
                      ->limit(3);
                },
            ])
            ->withCount('detalles') // 👈 total real de items
            ->where('periodo_id', $periodoId);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }
        if ($request->filled('desde')) {
            $query->whereDate('fecha_recepcion', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fecha_recepcion', '<=', $request->hasta);
        }
        if ($request->filled('buscar')) {
            $q = $request->buscar;
            $query->where(function ($sub) use ($q) {
                $sub->where('nro_compra', 'like', "%$q%")
                    ->orWhere('nro_factura', 'like', "%$q%")
                    ->orWhereHas('proveedor', fn($p) => $p->where('razon_social', 'like', "%$q%"));
            });
        }

        $compras = $query->orderBy('fecha_recepcion', 'desc')
            ->paginate(30)
            ->withQueryString();

        // 👇 nombres alineados con la vista Vue
        $totales = Compra::where('periodo_id', $periodoId)->selectRaw('
            COUNT(*) as total,
            COALESCE(SUM(total), 0)  as monto_total,
            COALESCE(SUM(iva_cf), 0) as total_iva,
            SUM(CASE WHEN estado = "confirmada"     THEN 1 ELSE 0 END) as confirmadas,
            SUM(CASE WHEN estado = "contabilizada"  THEN 1 ELSE 0 END) as contabilizadas
        ')->first();

        return Inertia::render('Inventarios/Compras/Index', [
            'periodo'     => $periodo,
            'compras'     => $compras,
            'totales'     => $totales,
            'proveedores' => ProveedorCliente::activos()->proveedores()
                                ->orderBy('razon_social')
                                ->get(['id', 'razon_social', 'nit']),
            'filters'     => $request->only(['estado', 'proveedor_id', 'desde', 'hasta', 'buscar']),
        ]);
    }

    // ── CREATE ───────────────────────────────────────────────────
    public function create($periodoId)
    {
        $periodo = Periodo::findOrFail($periodoId);

        return Inertia::render('Inventarios/Compras/Create', [
            'periodo'     => $periodo,
            'nro_compra'  => Compra::siguienteNumero($periodoId),
            'proveedores' => ProveedorCliente::activos()->proveedores()->orderBy('razon_social')
                ->get(['id', 'razon_social', 'nit', 'dias_credito', 'cuenta_por_pagar_id']),
            'almacenes'   => Almacen::activos()->get(['id', 'nombre', 'codigo']),
            'productos'   => Producto::activos()->conStock()->with('unidadMedida')
                ->orderBy('descripcion')->get(['id', 'codigo', 'descripcion', 'precio_costo', 'aplica_iva', 'unidad_medida_id']),

            // 🔥 Simplificado: solo búsqueda por nombre (sin depender de tipo ni códigos)
            'cuentas_pago' => Cuentas::query()
                ->where(function ($q) {
                    $q->where('nombre', 'like', '%caja%')
                    ->orWhere('nombre', 'like', '%banco%')
                    ->orWhere('nombre', 'like', '%pagar%')
                    ->orWhere('nombre', 'like', '%efectivo%');
                })
                ->orderBy('codigo')
                ->get(['id', 'codigo', 'nombre']),

            'cuentas_iva_cf' => Cuentas::query()
                ->where(function ($q) {
                    $q->where('nombre', 'like', '%iva%crédito%')
                    ->orWhere('nombre', 'like', '%iva%credito%')
                    ->orWhere('nombre', 'like', '%crédito fiscal%')
                    ->orWhere('nombre', 'like', '%credito fiscal%');
                })
                ->orderBy('codigo')
                ->get(['id', 'codigo', 'nombre']),
        ]);
    }

    // ── STORE (guardar borrador) ──────────────────────────────────
    public function store(CompraRequest $request, $periodoId)
    {
        $user = $request->user();

        $periodo = Periodo::where('id', $periodoId)
            ->where('empresa_id', $user->empresa_id)
            ->firstOrFail();

        if ($periodo->estado !== 'A') {
            return back()->withErrors(['error' => 'El período seleccionado no está abierto.']);
        }

        $data = $request->validated();
        $data['periodo_id'] = $periodo->id;
        $data['empresa_id'] = $user->empresa_id;
        $data['creado_por'] = $user->id;

        if (($data['forma_pago'] ?? null) === 'contado') {
            $data['dias_credito'] = 0;
            $data['fecha_vencimiento_pago'] = null;
        }

        $compra = DB::transaction(function () use ($data) {
            $compra = Compra::create([
                'empresa_id'             => $data['empresa_id'],
                'nro_compra'             => Compra::siguienteNumero($data['periodo_id']),
                'periodo_id'             => $data['periodo_id'],
                'proveedor_id'           => $data['proveedor_id'],
                'almacen_id'             => $data['almacen_id'],
                'nro_factura'            => $data['nro_factura'] ?? null,
                'cod_autorizacion'       => $data['cod_autorizacion'] ?? null,
                'fecha_factura'          => $data['fecha_factura'] ?? null,
                'fecha_recepcion'        => $data['fecha_recepcion'],
                'forma_pago'             => $data['forma_pago'],
                'dias_credito'           => $data['dias_credito'] ?? 0,
                'fecha_vencimiento_pago' => $data['fecha_vencimiento_pago'] ?? null,
                'cuenta_pago_id'         => $data['cuenta_pago_id'] ?? null,
                'cuenta_iva_cf_id'       => $data['cuenta_iva_cf_id'] ?? null,
                'observacion'            => $data['observacion'] ?? null,
                'estado'                 => 'borrador',
                'creado_por'             => $data['creado_por'],
            ]);

            foreach ($data['detalles'] as $det) {
                DetalleCompra::create([
                    'compra_id'      => $compra->id,
                    'producto_id'    => $det['producto_id'],
                    'cantidad'       => $det['cantidad'],
                    'costo_unitario' => $det['costo_unitario'],
                    'descuento'      => $det['descuento'] ?? 0,
                ]);
            }

            $compra->recalcularTotales();

            return $compra;
        });

        return redirect()
            ->route('inventario.compras.index', ['periodoId' => $data['periodo_id']])
            ->with('success', 'Compra registrada como borrador. Confírmala para ingresar el inventario.');
    }

    // ── CONFIRMAR: descuenta kardex ──────────────────────────────
    public function confirmar(Request $request, Compra $compra)
    {
        if (!$compra->esBorrador()) {
            return back()->withErrors(['error' => 'Solo se pueden confirmar compras en estado borrador.']);
        }

        try {
            DB::transaction(function () use ($compra) {
                $compra->load('detalles.producto');

                foreach ($compra->detalles as $det) {
                    if ($det->producto->tipo === 'servicio') continue; // servicios no mueven kardex

                        $mov = app(\App\Services\InventarioService::class)->registrarEntrada(
                            tipo: 'entrada_compra', productoId: $l['producto_id'], almacenId: $almacenId,
                            cantidad: $cantidad, costoUnitario: $costoUnitario, periodoId: $libro->periodo_id,
                            extra: [
                                'referencia_tipo' => 'compras', 'referencia_id' => $compra->id,
                                'proveedor_cliente_id' => $proveedor->id,
                                'fecha' => $libro->fecha_factura, 'motivo' => 'Compra Fact. ' . $libro->nro_factura,
                            ]
                        );
                        $detalle->update(['movimiento_id' => $mov->id]);   // guarda el DetalleCompra::create en $detalle
                }

                // Si tiene factura, crear entrada en libro_compras
                if ($compra->nro_factura) {
                    $libroCompra = $this->crearEntradaLibroCompras($compra);
                    $compra->update(['libro_compra_id' => $libroCompra->id]);
                }

                $compra->update([
                    'estado'          => 'confirmada',
                    'confirmado_por'  => auth()->id(),
                    'confirmado_at'   => now(),
                ]);
                if ($compra->forma_pago === 'credito') {
                    $proveedor = $compra->proveedor; // ya existe por FK
                    app(\App\Services\CuentaCorrienteService::class)->registrarCargo(
                        $proveedor,
                        (float) $compra->total,
                        'Compra ' . $compra->nro_compra . ($compra->nro_factura ? ' Fact. '.$compra->nro_factura : ''),
                        [
                            'fecha'             => $compra->fecha_recepcion,
                            'fecha_vencimiento' => $compra->fecha_vencimiento_pago,
                            'documento_tipo'    => 'compra',
                            'documento_numero'  => $compra->nro_factura ?? $compra->nro_compra,
                            'referencia_tipo'   => \App\Models\Compra::class,
                            'referencia_id'     => $compra->id,
                        ]
                    );
                }
            });

            return back()->with('success', "Compra {$compra->nro_compra} confirmada. Stock actualizado.");

        } catch (\Exception $e) {
            Log::error("Error confirmando compra {$compra->id}: " . $e->getMessage());
            return back()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    // ── CONTABILIZAR: genera asiento EGR ─────────────────────────
    public function contabilizar(Compra $compra)
    {
        try {
            app(\App\Services\ContabilizarCompraService::class)->contabilizarCompra($compra);
            return back()->with('success', "Compra {$compra->nro_compra} contabilizada y sincronizada con el Libro de Compras.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // ── SHOW ─────────────────────────────────────────────────────
    public function show(Compra $compra)
    {
        $compra->load([
            'proveedor', 'almacen', 'periodo',
            'detalles.producto.unidadMedida',
            'detalles.movimiento',
            'cuentaPago', 'cuentaIvaCf',
            'asiento.detalles.cuenta',
            'creadoPor', 'confirmadoPor',
        ]);

        return Inertia::render('Inventarios/Compras/Show', compact('compra'));
    }

    // ── ANULAR ───────────────────────────────────────────────────
    public function anular(Request $request, Compra $compra)
    {
        $request->validate(['motivo' => 'required|string|max:200']);

        if ($compra->estado === 'contabilizada') {
            return back()->withErrors(['error' => 'No se puede anular una compra ya contabilizada. Contacte al contador.']);
        }

        try {
            DB::transaction(function () use ($compra, $request) {
                // Revertir movimientos kardex si ya fue confirmada
                if ($compra->estaConfirmada()) {
                    foreach ($compra->detalles as $det) {
                        if ($det->movimiento_id) {
                            $det->movimiento->anular($request->motivo, auth()->id());
                            // Revertir stock_actual
                            $stock = \App\Models\StockActual::obtenerOCrear($det->producto_id, $compra->almacen_id);
                            $stock->aplicarSalida((float) $det->cantidad);
                        }
                    }
                }

                $compra->update(['estado' => 'anulada']);
            });
            if ($compra->estaConfirmada()) {
                $mov = \App\Models\MovimientoCuentaCorriente::where('referencia_tipo', \App\Models\Compra::class)
                    ->where('referencia_id', $compra->id)
                    ->where('tipo', 'cargo')
                    ->vigentes()
                    ->first();

                if ($mov) {
                    app(\App\Services\CuentaCorrienteService::class)->anular($mov);
                }
            }

            return back()->with('success', 'Compra anulada correctamente.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al anular: ' . $e->getMessage()]);
        }
    }

    // ── HELPER PRIVADO ───────────────────────────────────────────
    /*private function crearEntradaLibroCompras(Compra $compra): LibroCompra
    {
        return LibroCompra::create([
            'periodo_id'       => $compra->periodo_id,
            'nit_proveedor'    => $compra->proveedor->nit,
            'razon_social'     => $compra->proveedor->razon_social,
            'nro_factura'      => $compra->nro_factura,
            'cod_autorizacion' => $compra->cod_autorizacion,
            'fecha_factura'    => $compra->fecha_factura,
            'importe_total'    => $compra->total,
            'cf_iva_contable'  => $compra->iva_cf,
            'credito_fiscal'   => $compra->iva_cf,
            'importe_base_cf'  => $compra->subtotal - $compra->iva_cf,
            'subtotal'         => $compra->subtotal,
            'tipo_compra'      => '1', // compra local
            'concepto'         => 'Compra mercadería ' . $compra->nro_compra,
            'cuenta_pago_id'   => $compra->cuenta_pago_id,
            'cuenta_iva_cf_id' => $compra->cuenta_iva_cf_id,
            'importe_debe'     => $compra->subtotal,
            'importe_haber'    => $compra->total,
            'estado'           => 'pendiente',
            'importado_por'    => auth()->id(),
        ]);
    }*/
    public function recalcularTotales(): void
    {
        $this->loadMissing('detalles.producto');

        $subtotal = 0;
        $ivaCf    = 0;

        foreach ($this->detalles as $det) {
            $bruto = (float) $det->cantidad * (float) $det->costo_unitario - (float) $det->descuento;
            if ($det->producto?->aplica_iva) {
                $iva  = round($bruto * 0.13, 4);
                $base = round($bruto - $iva, 4);
            } else { $base = round($bruto, 4); $iva = 0; }
            $det->update(['subtotal' => $base]);   // siempre, no solo con IVA
            $subtotal += $base; $ivaCf += $iva;
        }

        $this->update([
            'subtotal' => $subtotal,              // 39.82
            'iva_cf'   => $ivaCf,                 // 5.18
            'total'    => round($subtotal + $ivaCf, 2),  // 45.00 ✅
        ]);
    }
private function crearEntradaLibroCompras(Compra $compra): LibroCompra
{
    // Si la compra ya tiene libro_compra_id, lo reutilizamos y aseguramos inventario
    if ($compra->libro_compra_id) {
        $libro = LibroCompra::findOrFail($compra->libro_compra_id);
        $this->marcarLibroIngresadoInventario($libro, $compra);
        $this->completarCuentasLibro($libro, $compra);
        return $libro;
    }

    // Si ya existe un libro vinculado a esta compra (por compra_id), reutilizarlo
    /*$existente = LibroCompra::where('compra_id', $compra->id)->first();
    if ($existente) {
        $compra->update(['libro_compra_id' => $existente->id]);
        $this->marcarLibroIngresadoInventario($existente, $compra);
        $this->completarCuentasLibro($existente, $compra);
        return $existente;
    }*/

    // 🔥 Resolver cuentas desde el producto y la compra
    $cuentaActivo  = $this->resolverCuentaActivo($compra);   // cuenta_activo_id del producto
    $cuentaPago    = $this->resolverCuentaPago($compra);
    $cuentaIvaCf   = $this->resolverCuentaIvaCf($compra);

    $libro = LibroCompra::create([
        'periodo_id'       => $compra->periodo_id,
        'compra_id'        => $compra->id,
        'nit_proveedor'    => $compra->proveedor->nit,
        'razon_social'     => $compra->proveedor->razon_social,
        'nro_factura'      => $compra->nro_factura,
        'cod_autorizacion' => $compra->cod_autorizacion,
        'fecha_factura'    => $compra->fecha_factura,
        'importe_total'    => $compra->total,
        'cf_iva_contable'  => $compra->iva_cf,
        'credito_fiscal'   => $compra->iva_cf,
        'importe_base_cf'  => round((float) $compra->subtotal - (float) $compra->iva_cf, 4),
        'subtotal'         => $compra->subtotal,
        'descuento'        => $compra->descuento ?? 0,
        'tipo_compra'      => '1',
        'concepto'         => 'Compra mercadería ' . $compra->nro_compra,

        // 🔥 Cuentas resueltas
        'cuenta_gasto_id'  => $cuentaActivo,   // cuenta_activo_id del producto (inventario)
        'cuenta_pago_id'   => $cuentaPago,
        'cuenta_iva_cf_id' => $cuentaIvaCf,

        'importe_debe'     => $compra->subtotal,
        'importe_haber'    => $compra->total,
        'forma_pago'       => $compra->forma_pago,
        'dias_credito'     => $compra->dias_credito ?? 0,
        'estado'           => 'pendiente',
        'importado_por'    => auth()->id(),

        // Inventario: ya viene de una compra que movió kardex
        'va_a_inventario'          => true,
        'almacen_id'               => $compra->almacen_id,
        'estado_inventario'        => 'ingresado',
        'ingresado_inventario_at'  => now(),
        'ingresado_inventario_por' => auth()->id(),
    ]);

    $compra->update(['libro_compra_id' => $libro->id]);
    return $libro;
}

/**
 * Marca un libro existente como ingresado a inventario (idempotente).
 */
private function marcarLibroIngresadoInventario(LibroCompra $libro, Compra $compra): void
{
    if ($libro->estaIngresadoAInventario()) {
        // Asegurar vínculo inverso aunque ya esté ingresado
        if (!$libro->compra_id) {
            $libro->update(['compra_id' => $compra->id]);
        }
        return;
    }

    $libro->update([
        'compra_id'                => $compra->id,
        'va_a_inventario'          => true,
        'almacen_id'               => $libro->almacen_id ?? $compra->almacen_id,
        'estado_inventario'        => 'ingresado',
        'ingresado_inventario_at'  => $libro->ingresado_inventario_at ?? now(),
        'ingresado_inventario_por' => $libro->ingresado_inventario_por ?? auth()->id(),
    ]);
}
/**
 * Devuelve la cuenta_activo_id común a todos los detalles.
 * Si hay varias distintas, devuelve la del primer detalle (o null si no hay).
 */
private function resolverCuentaActivo(Compra $compra): ?int
{
    $compra->loadMissing('detalles.producto');

    $cuentas = $compra->detalles
        ->pluck('producto.cuenta_activo_id')
        ->filter()
        ->unique()
        ->values();

    // Si todas las líneas comparten la misma cuenta → usarla
    if ($cuentas->count() === 1) {
        return (int) $cuentas->first();
    }

    // Si hay varias cuentas distintas, usar la del primer detalle
    if ($cuentas->count() > 1) {
        return (int) $cuentas->first();
    }

    return null; // ningún producto tiene cuenta_activo_id
}

/**
 * Resuelve la cuenta de pago: crédito → CxP del proveedor; si no, la de la compra.
 */
private function resolverCuentaPago(Compra $compra): ?int
{
    if ($compra->forma_pago === 'credito' && $compra->proveedor?->cuenta_por_pagar_id) {
        return (int) $compra->proveedor->cuenta_por_pagar_id;
    }
    return $compra->cuenta_pago_id ? (int) $compra->cuenta_pago_id : null;
}

/**
 * Resuelve la cuenta IVA CF: la de la compra o la default del sistema.
 */
private function resolverCuentaIvaCf(Compra $compra): ?int
{
    if ($compra->cuenta_iva_cf_id) {
        return (int) $compra->cuenta_iva_cf_id;
    }

    $cuenta = \App\Models\Cuentas::where('nombre', 'IVA CRÉDITO FISCAL')
        ->where('permite_movimiento', true)
        ->first();

    return $cuenta?->id;
}
}
