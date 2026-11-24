<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\PagoVenta;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\Periodo;
use App\Models\ProveedorCliente;
use App\Models\Cuentas;
use App\Models\LibroVenta;
use App\Models\CierreCaja;
use App\Models\ConfiguracionSiat;
use App\Services\InventarioService;
use App\Services\SiatService;
use Illuminate\Http\Request;
use App\Models\StockActual;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SiatPuntoVenta;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use App\Services\CuentaCorrienteService;

class VentaController extends Controller
{
public function __construct(
    private InventarioService $inventario,
    private SiatService $siat,
    private CuentaCorrienteService $cuentaCorriente,
    private \App\Services\ContabilizarVentaService $contabilizador,
) {}

    // ══════════════════════════════════════════════════════════════
    //  INDEX
    // ══════════════════════════════════════════════════════════════
    public function index(Request $request, $periodoId)
    {
        $periodo = Periodo::findOrFail($periodoId);

        $query = Venta::with(['almacen', 'cliente'])
            ->where('periodo_id', $periodoId);

        if ($request->filled('estado'))
            $query->where('estado', $request->estado);
        if ($request->filled('estado_siat'))
            $query->where('estado_siat', $request->estado_siat);
        if ($request->filled('estado_contable'))
            $query->where('estado_contable', $request->estado_contable);
        if ($request->filled('almacen_id'))
            $query->where('almacen_id', $request->almacen_id);
        if ($request->filled('desde'))
            $query->where('fecha', '>=', $request->desde);
        if ($request->filled('hasta'))
            $query->where('fecha', '<=', $request->hasta);
        if ($request->filled('buscar')) {
            $q = $request->buscar;
            $query->where(function ($s) use ($q) {
                $s->where('nro_venta', 'like', "%$q%")
                  ->orWhere('nro_factura', 'like', "%$q%")
                  ->orWhere('razon_social_cliente', 'like', "%$q%")
                  ->orWhere('nit_cliente', 'like', "%$q%")
                  ->orWhere('ci_cliente', 'like', "%$q%");
            });
        }

        $ventas = $query->orderBy('fecha', 'desc')
                        ->orderBy('id', 'desc')
                        ->paginate(50)
                        ->withQueryString();

        $totales = Venta::where('periodo_id', $periodoId)
            ->where('estado', 'confirmada')
            ->selectRaw('
                COUNT(*) as total,
                SUM(total) as monto_total,
                SUM(iva_df) as total_iva,
                SUM(CASE WHEN estado_contable="pendiente" THEN 1 ELSE 0 END) as pendientes_contabilizar,
                SUM(CASE WHEN estado_siat="pendiente" THEN 1 ELSE 0 END) as pendientes_siat
            ')->first();

        return Inertia::render('Inventarios/Ventas/Index', [
            'periodo'   => $periodo,
            'ventas'    => $ventas,
            'totales'   => $totales,
            'almacenes' => Almacen::activos()->get(['id', 'nombre']),
            'filters'   => $request->only([
                'estado', 'estado_siat', 'estado_contable',
                'almacen_id', 'desde', 'hasta', 'buscar',
            ]),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  CREATE — formulario de nueva venta
    // ══════════════════════════════════════════════════════════════
    public function create($periodoId)
    {
        $periodo = Periodo::findOrFail($periodoId);

        // 🔥 Defaults para no obligar al cajero a elegirlos siempre
        $cuentaIvaDfDefault = Cuentas::where('nombre', 'IVA DÉBITO FISCAL')
            ->where('permite_movimiento', true)
            ->first();

        $cuentaCobroDefault = Cuentas::where(function ($q) {
                $q->where('nombre', 'like', '%caja%')
                  ->orWhere('nombre', 'like', '%efectivo%');
            })
            ->where('permite_movimiento', true)
            ->orderBy('codigo')
            ->first();

        return Inertia::render('Inventarios/Ventas/Create', [
            'periodo'   => $periodo,
            'almacenes' => Almacen::activos()->get(['id', 'codigo', 'nombre']),

            'clientes'  => ProveedorCliente::activos()->clientes()
                                ->orderBy('razon_social')
                                ->get(['id', 'razon_social', 'nit', 'ci', 'complemento', 'cuenta_por_cobrar_id', 'dias_credito']),

            // 🔥 Solo productos con cuentas contables completas
            'productos' => Producto::activos()->conStock()
                ->whereNotNull('cuenta_ingreso_id')
                ->where(fn($q) => $q->where('controla_stock', false)
                    ->orWhere(fn($s) => $s->whereNotNull('cuenta_activo_id')
                                        ->whereNotNull('cuenta_costo_venta_id')))
                ->with('unidadMedida')
                ->orderBy('descripcion')
                ->get(),

            'cuentas_cobro' => Cuentas::where(function ($q) {
                                    $q->where('nombre', 'like', '%caja%')
                                    ->orWhere('nombre', 'like', '%banco%')
                                    ->orWhere('nombre', 'like', '%efectivo%')
                                    ->orWhere('nombre', 'like', '%cobrar%');
                                })->orderBy('codigo')->get(['id', 'codigo', 'nombre']),

            'cuentas_iva' => Cuentas::where(function ($q) {
                                    $q->where('nombre', 'like', '%iva%débito%')
                                    ->orWhere('nombre', 'like', '%iva%debito%')
                                    ->orWhere('nombre', 'like', '%débito fiscal%')
                                    ->orWhere('nombre', 'like', '%debito fiscal%')
                                    ->orWhere('codigo', 'like', '2.1.2%');
                                })->orderBy('codigo')->get(['id', 'codigo', 'nombre']),

            'cuenta_iva_df_default' => $cuentaIvaDfDefault,
            'cuenta_cobro_default'  => $cuentaCobroDefault,

            'configSiat' => ConfiguracionSiat::activa()
                                ? [
                                    'cufd_vigente' => ConfiguracionSiat::activa()->cufdVigente(),
                                    'es_online'    => ConfiguracionSiat::activa()->esOnline(),
                                    'ambiente'     => ConfiguracionSiat::activa()->esPiloto() ? 'Piloto' : 'Producción',
                                ]
                                : null,
            'fecha'     => today()->toDateString(),
        ]);
    }


    // ══════════════════════════════════════════════════════════════
    //  STORE — confirma la venta completa en una transacción
    //  Flujo: validar stock → crear venta → detalles → pagos
    //         → SIAT (si tiene factura) → kardex-- → asiento (opcional)
    // ══════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $request->validate([
            'periodo_id'           => 'required|exists:periodos,id',
            'almacen_id'           => 'required|exists:almacenes,id',
            'fecha'                => 'required|date',
            'razon_social_cliente' => 'required|string|max:200',
            'nit_cliente'          => 'nullable|string|max:20',
            'ci_cliente'           => 'nullable|string|max:15',
            'complemento_cliente'  => 'nullable|string|max:5',
            'proveedor_cliente_id' => 'nullable|exists:proveedores_clientes,id',
            'forma_pago'           => 'required|in:contado,credito,mixto,tarjeta,qr',
            'dias_credito'         => 'nullable|integer|min:0',
            'con_factura'          => 'boolean',
            'cuenta_cobro_id'      => 'nullable|exists:cuentas,id',
            'cuenta_iva_df_id'     => 'nullable|exists:cuentas,id',
            'contabilizar_ahora'   => 'boolean',
            'detalles'             => 'required|array|min:1',
            'detalles.*.producto_id'     => 'required|exists:productos,id',
            'detalles.*.cantidad'        => 'required|numeric|min:0.0001',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.descuento'       => 'nullable|numeric|min:0',
            'pagos'                => 'required|array|min:1',
            'pagos.*.medio'        => 'required|in:efectivo,tarjeta_debito,tarjeta_credito,qr,transferencia,cheque,credito',
            'pagos.*.monto'        => 'required|numeric|min:0.01',
            'pagos.*.cuenta_id'    => 'nullable|exists:cuentas,id',
            'pagos.*.cambio' => 'nullable|numeric|min:0',
        ]);

        try {
            return DB::transaction(function () use ($request) {

                // ── 0. Resolver empresa ─────────────────────────────
                $empresaId = Periodo::find($request->periodo_id)?->empresa_id
                        ?? auth()->user()?->empresa_id
                        ?? session('empresa_id')
                        ?? \App\Models\Empresa::value('id');

                if (!$empresaId) {
                    throw new \Exception('No se pudo determinar la empresa para esta venta.');
                }

                // ── 1. Validar stock ────────────────────────────────
                foreach ($request->detalles as $det) {
                    $prod = Producto::findOrFail($det['producto_id']);
                    if ($prod->controla_stock && !$prod->hayStock($det['cantidad'], $request->almacen_id)) {
                        $disp = $prod->stockEnAlmacen($request->almacen_id);
                        throw new \Exception(
                            "Stock insuficiente: '{$prod->descripcion}'. "
                            . "Disponible: {$disp} | Requerido: {$det['cantidad']}"
                        );
                    }
                }

                // ── 2. Generar número CON BLOQUEO ──────────────────
                $nroVenta = Venta::siguienteNumero($request->periodo_id, $empresaId);

                $conFactura = $request->boolean('con_factura', true);
                $configSiat = ConfiguracionSiat::activa();
                $siatOnline = $configSiat
                    && $configSiat->cufdVigente()
                    && $configSiat->esOnline();

                if (!$conFactura) {
                    $modalidad  = 'sin_factura';
                    $estadoSiat = 'sin_factura';
                } elseif ($siatOnline) {
                    $modalidad  = 'online';
                    $estadoSiat = 'pendiente';
                } else {
                    $modalidad  = 'offline';
                    $estadoSiat = 'pendiente';
                }

                // ── 3. Crear cabecera ───────────────────────────────
                $venta = Venta::create([
                    'empresa_id'              => $empresaId,
                    'nro_venta'               => $nroVenta,
                    'periodo_id'              => $request->periodo_id,
                    'almacen_id'              => $request->almacen_id,
                    'proveedor_cliente_id'    => $request->proveedor_cliente_id,
                    'nit_cliente'             => $request->nit_cliente,
                    'ci_cliente'              => $request->ci_cliente,
                    'complemento_cliente'     => $request->complemento_cliente,
                    'razon_social_cliente'    => $request->razon_social_cliente,
                    'fecha'                   => $request->fecha,
                    'hora'                    => now()->format('H:i:s'),
                    'tipo'                    => 'venta',
                    'forma_pago'              => $request->forma_pago,
                    'dias_credito'            => $request->dias_credito ?? 0,
                    'fecha_vencimiento_cobro' => $request->dias_credito
                        ? now()->addDays($request->dias_credito)->toDateString()
                        : null,
                    'con_factura'             => $conFactura,
                    'modalidad_factura'       => $modalidad,
                    'cuenta_cobro_id'         => $request->cuenta_cobro_id,
                    'cuenta_iva_df_id'        => $request->cuenta_iva_df_id,
                    'estado'                  => 'borrador',
                    'estado_siat'             => $estadoSiat,
                    'estado_contable'         => 'pendiente',
                    'creado_por'              => auth()->id(),
                ]);

                // ── 4. Detalles ─────────────────────────────────────
                foreach ($request->detalles as $det) {
                    $prod          = Producto::findOrFail($det['producto_id']);
                    $costoUnitario = $prod->costoPromedioEnAlmacen($request->almacen_id);

                    DetalleVenta::create([
                        'venta_id'        => $venta->id,
                        'producto_id'     => $det['producto_id'],
                        'cantidad'        => $det['cantidad'],
                        'precio_unitario' => $det['precio_unitario'],
                        'descuento'       => $det['descuento'] ?? 0,
                        'costo_unitario'  => $costoUnitario,
                    ]);
                }

                $venta->recalcularTotales();
                $venta->refresh()->load('detalles.producto');

                // ── 5. Pagos ────────────────────────────────────────
                foreach ($request->pagos as $pago) {
                    PagoVenta::create([
                        'venta_id'   => $venta->id,
                        'medio'      => $pago['medio'],
                        'monto'      => $pago['monto'],
                        'cambio'     => $pago['cambio'] ?? 0,
                        'fecha'      => $request->fecha,
                        'referencia' => $pago['referencia'] ?? null,
                        'cuenta_id'  => $pago['cuenta_id'] ?? null,
                    ]);
                }

                // ── 6. Emitir factura SIAT (deshabilitado por ahora) ──
                if ($conFactura && $modalidad !== 'sin_factura') {
                    $venta->update([
                        'estado_siat'    => 'pendiente',
                        'respuesta_siat' => json_encode(['_deshabilitado' => true]),
                    ]);
                }

                // ── 7. Kardex: descontar stock ──────────────────────
                foreach ($venta->detalles as $detalle) {
                    if (!$detalle->producto->controla_stock) continue;

                    $resultado = $this->inventario->registrarSalida(
                        tipo:       'salida_venta',
                        productoId: $detalle->producto_id,
                        almacenId:  $venta->almacen_id,
                        cantidad:   (float) $detalle->cantidad,
                        periodoId:  $venta->periodo_id,
                        extra: [
                            'precio_venta' => (float) $detalle->subtotal,  
                            'referencia_tipo'      => 'ventas',
                            'referencia_id'        => $venta->id,
                            'fecha'                => $venta->fecha,
                            'proveedor_cliente_id' => $venta->proveedor_cliente_id,
                            'motivo'               => 'Venta ' . $nroVenta,
                        ]
                    );

                    $detalle->update([
                        'costo_unitario' => $resultado['costo_unitario'],
                        'costo_total'    => $resultado['costo_total'],
                        'utilidad'     => round((float) $detalle->subtotal - $resultado['costo_total'], 4), 
                        'movimiento_id'  => $resultado['movimiento']->id,
                    ]);
                }

                // ── 8. Cierre de caja del día ───────────────────────
                $cierre = CierreCaja::obtenerHoy($venta->almacen_id, $venta->periodo_id);
                $cierre->recalcularTotales();

                $venta->update(['estado' => 'confirmada']);

                // ── 9. Contabilizar ahora o dejar para el cierre ────
                // ══════════════════════════════════════════════════════
                // ── 10. CUENTA CORRIENTE (solo si queda saldo) ───────
                // ══════════════════════════════════════════════════════
                $totalVenta   = (float) $venta->total;
                $totalPagado = (float) collect($request->pagos)->where('medio', '!=', 'credito')->sum(fn($p) => (float) $p['monto'] - (float) ($p['cambio'] ?? 0));
                $saldoCredito = round($totalVenta - $totalPagado, 2);

                if ($saldoCredito > 0) {

                    // Resolver el ProveedorCliente
                    $pc = null;

                    if ($venta->proveedor_cliente_id) {
                        $pc = \App\Models\ProveedorCliente::find($venta->proveedor_cliente_id);
                    }

                    // Si no hay cliente seleccionado pero hay NIT/CI, buscar o crear
                    if (!$pc && ($venta->nit_cliente || $venta->ci_cliente)) {
                        $pc = \App\Models\ProveedorCliente::firstOrCreate(
                            [
                                'empresa_id' => $empresaId,
                                'nit'        => $venta->nit_cliente ?: $venta->ci_cliente,
                            ],
                            [
                                'razon_social' => $venta->razon_social_cliente,
                                'tipo'         => 'cliente',
                                'activo'       => true,
                            ]
                        );

                        // Actualiza la venta con el id resuelto
                        $venta->update(['proveedor_cliente_id' => $pc->id]);
                    }
                    // paso 10: justo después de resolver $pc, antes de registrarCargo
                    if (!$pc) {
                        throw new \Exception('Para vender a crédito indica un cliente (selecciónalo o escribe su NIT/CI).');
                    }

                    if ($pc) {
                        $this->cuentaCorriente->registrarCargo(
                            pc:       $pc,
                            monto:    $saldoCredito,
                            concepto: "Venta {$venta->nro_venta}"
                                . ($venta->nro_factura ? " - Fact. {$venta->nro_factura}" : ''),
                            opts: [
                                'fecha'             => $venta->fecha,
                                'fecha_vencimiento' => $venta->fecha_vencimiento_cobro,
                                'documento_tipo'    => $venta->con_factura ? 'factura' : 'recibo',
                                'documento_numero'  => $venta->nro_factura ?? $venta->nro_venta,
                                'referencia_tipo'   => 'ventas',
                                'referencia_id'     => $venta->id,
                                'creado_por'        => auth()->id(),
                            ]
                        );
                    }
                }

                // ══════════════════════════════════════════════════════
                // ── 11. LIBRO DE VENTAS (solo si con_factura) ────────
                // ══════════════════════════════════════════════════════
                if ($venta->con_factura && $venta->nro_factura) {
                    // Idempotencia: evita duplicados si el flujo se reintenta
                    $existe = LibroVenta::where('venta_id', $venta->id)->exists();

                    if (!$existe) {
                        // Cuenta de ingreso: toma la del primer producto, o la de la venta
                        $cuentaIngreso = $venta->detalles->first()?->producto?->cuenta_ingreso_id
                                    ?? null;

                        LibroVenta::create([
                            'periodo_id'            => $venta->periodo_id,
                            'mes'                   => (int) \Carbon\Carbon::parse($venta->fecha)->format('n'),
                            'nro_correlativo'       => null,
                            'especificacion'        => 'VENTA',
                            'fecha_factura'         => $venta->fecha,
                            'nro_factura'           => $venta->nro_factura,
                            'cod_autorizacion'      => null,
                            'nit_cliente'           => $venta->nit_cliente,
                            'complemento'           => $venta->complemento_cliente,
                            'razon_social_cliente'  => $venta->razon_social_cliente,
                            'importe_total'         => round((float) $venta->total, 4),
                            'importe_ice'           => 0,
                            'importe_iehd'          => 0,
                            'importe_ipj'           => 0,
                            'tasas'                 => 0,
                            'otros_no_sujetos'      => 0,
                            'exportaciones_exentas' => 0,
                            'ventas_tasa_cero'      => 0,
                            'subtotal'              => round((float) $venta->subtotal, 4),
                            'descuento'             => round((float) $venta->descuento, 4),
                            'importe_gift_card'     => 0,
                            'importe_base_df'       => round((float) $venta->total - (float) $venta->iva, 4),
                            'debito_fiscal'         => round((float) $venta->iva, 4),

                            'cuenta_ingreso_id'       => $cuentaIngreso,
                            'cuenta_iva_df_id'        => $venta->cuenta_iva_df_id,
                            'cuenta_descuento_id'     => null,
                            'cuenta_iva_descuento_id' => null,
                            'cuenta_cobro_id'         => $venta->cuenta_cobro_id,

                            'estado'                => 'pendiente',
                            'estado_factura'        => 'valida',
                            'tipo_venta'            => 'propia',
                            'importe_debe'          => 0,
                            'importe_haber'         => 0,

                            // 🔥 Inventario: ya se descontó en el paso 7
                            'venta_id'              => $venta->id,
                            'va_a_inventario'       => false,
                            'almacen_id'            => $venta->almacen_id,
                            'estado_inventario'     => 'no_aplica',

                            'importado_por'         => auth()->id(),
                        ]);
                    }
                }

                // ── 12. Redirect final ──────────────────────────────
                return redirect()
                    ->route('inventario.ventas.show', [$request->periodo_id, $venta->id])
                    ->with('success', "Venta {$nroVenta} registrada correctamente."
                        . ($venta->nro_factura ? " Factura: {$venta->nro_factura}." : '')
                );
            });

        } catch (\Exception $e) {
            Log::error('Error creando venta: ' . $e->getMessage());
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }
    // ══════════════════════════════════════════════════════════════
    //  SHOW
    // ══════════════════════════════════════════════════════════════
    public function show($periodoId, Venta $venta)
    {
        $venta->load([
            'detalles.producto.unidadMedida',
            'detalles.movimiento',
            'pagos.cuenta',
            'almacen',
            'cliente',
            'asiento.detalles.cuenta',
            'creadoPor',
            'contabilizadoPor',
        ]);

        return Inertia::render('Inventarios/Ventas/Show', [
            'venta'   => $venta,
            'periodo' => Periodo::find($periodoId),
            'empresa' => \App\Models\Empresa::first(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  CONTABILIZAR — genera asiento ING para una venta
    // ══════════════════════════════════════════════════════════════
    public function contabilizar($periodoId, Venta $venta)
    {
        try {
            $this->contabilizador->contabilizarVenta($venta);
            return back()->with('success', 'Venta contabilizada y sincronizada con el Libro de Ventas.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  CONTABILIZAR MASIVO
    // ══════════════════════════════════════════════════════════════
    public function contabilizarMasivo(Request $request, $periodoId)
    {
        $request->validate([
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date',
            'ids'   => 'nullable|array',
        ]);

        $query = Venta::where('periodo_id', $periodoId)
            ->where('estado', 'confirmada')
            ->where('estado_contable', 'pendiente')
            ->with('detalles.producto', 'pagos');

        if (!empty($request->ids)) {
            $query->whereIn('id', $request->ids);
        } else {
            if ($request->desde) $query->where('fecha', '>=', $request->desde);
            if ($request->hasta) $query->where('fecha', '<=', $request->hasta);
        }

        $ventas  = $query->get();
        $ok      = 0;
        $errores = [];

        foreach ($ventas as $venta) {
            try {
                $this->contabilizador->contabilizarVenta($venta);
                $ok++;
            } catch (\Exception $e) {
                $errores[] = "{$venta->nro_venta}: " . $e->getMessage();
            }
        }

        $msg = "{$ok} ventas contabilizadas.";
        if ($errores) $msg .= ' Errores: ' . implode(' | ', $errores);

        return back()->with('success', $msg);
    }

    // ══════════════════════════════════════════════════════════════
    //  ANULAR
    // ══════════════════════════════════════════════════════════════
    public function anular(Request $request, $periodoId, Venta $venta)
    {
        $request->validate(['motivo' => 'required|string|max:200']);

        if ($venta->estaAnulada()) {
            return back()->withErrors(['error' => 'La venta ya está anulada.']);
        }

        try {
            DB::transaction(function () use ($venta, $request) {

                // 1. Anular en SIAT si tiene factura aprobada
                // 1. Anular en SIAT si tiene factura aprobada
                if ($venta->facturaAprobada() && $venta->con_factura) {
                    try {
                        $pv = $this->puntoVentaSiat();
                        $this->siat->anularFactura($venta, $pv);
                    } catch (\Exception $e) {
                        Log::warning("No se pudo anular en SIAT: " . $e->getMessage());
                        // No bloquear la anulación local si SIAT falla
                    }
                }

                // 2. Revertir kardex
                $venta->load('detalles');
                foreach ($venta->detalles as $det) {
                    if (!$det->movimiento_id || !$det->movimiento?->estaActivo()) continue;

                    $stock = StockActual::obtenerOCrear($det->producto_id, $venta->almacen_id);
                    $stock->revertirSalida((float) $det->cantidad, (float) $det->costo_unitario);

                    $det->movimiento->anular($request->motivo, auth()->id());
                }

                // 3. Revertir asiento si fue contabilizada
                if ($venta->estaContabilizada() && $venta->asiento_id) {
                    $venta->asiento->detalles()->delete();
                    $venta->asiento->delete();
                }

                $venta->update([
                    'estado'           => 'anulada',
                    'motivo_anulacion' => $request->motivo,
                    'estado_contable'  => 'pendiente',
                    'asiento_id'       => null,
                ]);
            });

            return back()->with('success', "Venta {$venta->nro_venta} anulada.");

        } catch (\Exception $e) {
            Log::error("Error anulando venta {$venta->id}: " . $e->getMessage());
            return back()->withErrors(['error' => 'Error al anular: ' . $e->getMessage()]);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  REENVIAR AL SIAT (facturas pendientes/rechazadas)
    // ══════════════════════════════════════════════════════════════
    public function reenviarSiat($periodoId, Venta $venta)
    {
        if (!in_array($venta->estado_siat, ['pendiente', 'rechazada'])) {
            return back()->withErrors([
                'error' => 'Solo se pueden reenviar facturas pendientes o rechazadas.'
            ]);
        }

        try {
            $pv = $this->puntoVentaSiat();
            $resultado = $this->siat->emitirFactura($venta, $pv);
            $venta->update($resultado);

            return back()->with('success', 'Factura enviada al SIN. Estado: ' . $resultado['estado_siat']);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error SIAT: ' . $e->getMessage()]);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  IMPRIMIR FACTURA
    // ══════════════════════════════════════════════════════════════
    public function imprimirFactura($periodoId, Venta $venta)
    {
        $venta->load([
            'detalles.producto.unidadMedida',  // ✅ unidad de medida
            'detalles.producto',               // para codigo
            'pagos.cuenta',
            'almacen',
            'cliente',
        ]);

        // ✅ Generar QR con el CUF (si existe)
        $qrBase64 = null;

        if ($venta->cuf) {
            $empresa = \App\Models\Empresa::first();

            $qrUrl = 'https://www.impuestos.gob.bo/consulta/QR?'
                . 'nit=' . urlencode($empresa?->nit ?? '')
                . '&t=' . urlencode($venta->cuf)
                . '&c=' . urlencode($venta->nro_factura ?? '')
                . '&f=' . urlencode(
                    $venta->fecha_emision_factura ?? $venta->fecha
                );

            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($qrUrl)
                ->size(200)
                ->margin(1)
                ->build();

            $qrBase64 = 'data:image/png;base64,'
                . base64_encode($result->getString());
        }

        return Inertia::render('Inventarios/Ventas/VentasFactura', [
            'venta'   => $venta,
            'config'  => ConfiguracionSiat::activa(),
            'empresa' => \App\Models\Empresa::first(),
            'qr'      => $qrBase64,   // ✅ pasar QR
        ]);
    }
    /**
     * Resuelve el punto de venta SIAT activo para el usuario actual.
     * Prioridad: PV asignado al usuario (por_defecto) → primer PV activo de la empresa.
     */
    private function puntoVentaSiat(): SiatPuntoVenta
    {
        $pv = SiatPuntoVenta::porDefectoPara(auth()->user())
            ?? SiatPuntoVenta::where('empresa_id', session('empresa_id'))
                ->where('activo', true)
                ->orderBy('id')
                ->first();

        if (!$pv) {
            throw new \RuntimeException(
                'No hay punto de venta SIAT configurado. Ve a Configuración → SIAT.'
            );
        }

        return $pv;
    }
    public function recalcularTotales(): void
    {
        $this->unsetRelation('detalles');
        $this->load('detalles.producto');

        $subtotal = 0.0;
        $ivaDf    = 0.0;

        foreach ($this->detalles as $det) {
            $bruto = (float) $det->cantidad * (float) $det->precio_unitario - (float) ($det->descuento ?? 0);

            if ($det->producto?->aplica_iva) {          // el precio incluye IVA
                $iva  = round($bruto * 0.13, 4);
                $base = round($bruto - $iva, 4);
            } else {
                $iva  = 0.0;
                $base = round($bruto, 4);
            }

            $det->update(['subtotal' => $base]);
            $subtotal += $base;
            $ivaDf    += $iva;
        }

        $this->update([
            'subtotal' => round($subtotal, 2),
            'iva_df'   => round($ivaDf, 2),
            'total'    => round($subtotal + $ivaDf, 2),
        ]);
    }
    /**
     * Crea (o reutiliza) la fila en libro_ventas para esta venta
     * y la marca como ya ingresada a inventario.
     */
    private function crearEntradaLibroVentas(Venta $venta): LibroVenta
    {
        // Si ya tiene libro_venta_id, reutilizar y asegurar inventario
        if ($venta->libro_venta_id) {
            $libro = LibroVenta::findOrFail($venta->libro_venta_id);
            $this->marcarLibroVentaIngresadoInventario($libro, $venta);
            $this->completarCuentasLibroVenta($libro, $venta);
            return $libro;
        }

        // Si ya existe un libro vinculado a esta venta, reutilizar
        $existente = LibroVenta::where('venta_id', $venta->id)->first();
        if ($existente) {
            $venta->update(['libro_venta_id' => $existente->id]);
            $this->marcarLibroVentaIngresadoInventario($existente, $venta);
            $this->completarCuentasLibroVenta($existente, $venta);
            return $existente;
        }

        $cuentaIngreso = $this->resolverCuentaIngresoVenta($venta);
        $cuentaCobro   = $this->resolverCuentaCobroVenta($venta);
        $cuentaIvaDf   = $this->resolverCuentaIvaDfVenta($venta);

        $libro = LibroVenta::create([
            'periodo_id'            => $venta->periodo_id,
            'venta_id'              => $venta->id,
            'nit_cliente'           => $venta->nit_cliente ?? $venta->ci_cliente,
            'complemento'           => $venta->complemento_cliente,
            'razon_social_cliente'  => $venta->razon_social_cliente,
            'nro_factura'           => $venta->nro_factura,
            'cod_autorizacion'      => $venta->cod_autorizacion,
            'fecha_factura'         => $venta->fecha,
            'importe_total'         => $venta->total,
            'debito_fiscal'         => $venta->iva_df,
            'importe_base_df'       => round((float) $venta->subtotal, 4),
            'subtotal'              => $venta->subtotal,
            'descuento'             => $venta->descuento ?? 0,
            'tipo_venta'            => '1',
            'especificacion'        => 'Venta mercadería ' . $venta->nro_venta,

            // Cuentas resueltas
            'cuenta_ingreso_id'     => $cuentaIngreso,
            'cuenta_cobro_id'       => $cuentaCobro,
            'cuenta_iva_df_id'      => $cuentaIvaDf,

            'importe_debe'          => $venta->total,
            'importe_haber'         => $venta->subtotal,
            'estado'                => 'pendiente',
            'importado_por'         => auth()->id(),

            // Inventario
            'va_a_inventario'          => true,
            'almacen_id'               => $venta->almacen_id,
            'estado_inventario'        => 'ingresado',
            'ingresado_inventario_at'  => now(),
            'ingresado_inventario_por' => auth()->id(),
        ]);

        $venta->update(['libro_venta_id' => $libro->id]);
        return $libro;
    }

    /**
     * Marca un libro de venta existente como ingresado a inventario.
     */
    private function marcarLibroVentaIngresadoInventario(LibroVenta $libro, Venta $venta): void
    {
        if ($libro->estaIngresadoAInventario()) {
            if (!$libro->venta_id) {
                $libro->update(['venta_id' => $venta->id]);
            }
            return;
        }

        $libro->update([
            'venta_id'                 => $venta->id,
            'va_a_inventario'          => true,
            'almacen_id'               => $libro->almacen_id ?? $venta->almacen_id,
            'estado_inventario'        => 'ingresado',
            'ingresado_inventario_at'  => $libro->ingresado_inventario_at ?? now(),
            'ingresado_inventario_por' => $libro->ingresado_inventario_por ?? auth()->id(),
        ]);
    }

    /**
     * Rellena solo las cuentas vacías del libro (no sobrescribe las del contador).
     */
    private function completarCuentasLibroVenta(LibroVenta $libro, Venta $venta): void
    {
        $updates = [];

        if (!$libro->cuenta_ingreso_id) {
            $updates['cuenta_ingreso_id'] = $this->resolverCuentaIngresoVenta($venta);
        }
        if (!$libro->cuenta_cobro_id) {
            $updates['cuenta_cobro_id'] = $this->resolverCuentaCobroVenta($venta);
        }
        if (!$libro->cuenta_iva_df_id) {
            $updates['cuenta_iva_df_id'] = $this->resolverCuentaIvaDfVenta($venta);
        }
        if (!$libro->venta_id) {
            $updates['venta_id'] = $venta->id;
        }

        $updates = array_filter($updates, fn($v) => $v !== null);

        if (!empty($updates)) {
            $libro->update($updates);
        }
    }

    private function resolverCuentaIngresoVenta(Venta $venta): ?int
    {
        $venta->loadMissing('detalles.producto');
        $cuentas = $venta->detalles
            ->pluck('producto.cuenta_ingreso_id')
            ->filter()
            ->unique()
            ->values();

        return $cuentas->count() >= 1 ? (int) $cuentas->first() : null;
    }

    private function resolverCuentaCobroVenta(Venta $venta): ?int
    {
        if ($venta->cuenta_cobro_id) {
            return (int) $venta->cuenta_cobro_id;
        }

        // Crédito → CxC del cliente
        if ($venta->forma_pago === 'credito' && $venta->cliente?->cuenta_por_cobrar_id) {
            return (int) $venta->cliente->cuenta_por_cobrar_id;
        }

        // Fallback: primer pago con cuenta
        $venta->loadMissing('pagos');
        $pago = $venta->pagos->firstWhere('cuenta_id', '!=', null);
        return $pago?->cuenta_id ? (int) $pago->cuenta_id : null;
    }

    private function resolverCuentaIvaDfVenta(Venta $venta): ?int
    {
        if ($venta->cuenta_iva_df_id) {
            return (int) $venta->cuenta_iva_df_id;
        }

        $cuenta = \App\Models\Cuentas::where('nombre', 'IVA DÉBITO FISCAL')
            ->where('permite_movimiento', true)
            ->first();

        return $cuenta?->id;
    }
}
