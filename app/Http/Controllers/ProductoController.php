<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\UnidadMedida;
use App\Models\Almacen;
use App\Models\StockActual;
use App\Models\MovimientoInventario;
use App\Models\Cuentas;           // ← corregido: Cuenta (singular)
use App\Models\Periodo;
use App\Http\Requests\Inventario\ProductoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
{
    // ── INDEX ────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Producto::with(['categoria', 'unidadMedida'])
            ->withSum('stockActual as stock_total', 'cantidad');

        if ($request->filled('buscar')) {
            $query->buscar($request->buscar);
        }
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo === 'true');
        }
        if ($request->filled('bajo_minimo') && $request->bajo_minimo === 'true') {
            $query->whereHas('stockActual', function ($q) {
                $q->whereColumn('cantidad', '<', 'productos.stock_minimo')
                  ->where('productos.stock_minimo', '>', 0);
            });
        }

        $productos = $query->orderBy('descripcion')->paginate(50)->withQueryString();

        return Inertia::render('Inventarios/Productos/Index', [
            'productos'   => $productos,
            'categorias'  => CategoriaProducto::activos()->orderBy('nombre')->get(),
            'filters'     => $request->only(['buscar', 'categoria_id', 'tipo', 'activo', 'bajo_minimo']),
            'resumen'     => [
                'total'        => Producto::activos()->count(),
                'bajo_minimo'  => $this->contarBajoMinimo(),
                'sin_cuentas'  => Producto::activos()
                    ->whereNull('cuenta_activo_id')->count(),
            ],
        ]);
    }

    // ── CREATE ───────────────────────────────────────────────────
    public function create()
    {
        return Inertia::render('Inventarios/Productos/Create', [
            'categorias' => CategoriaProducto::activos()->orderBy('nombre')->get(),
            'unidades'   => UnidadMedida::activos()->orderBy('nombre')->get(),
            'cuentas'    => Cuentas::orderBy('codigo')->get(['id', 'codigo', 'nombre', 'tipo']),
        ]);
    }

    // ── STORE ────────────────────────────────────────────────────
    public function store(ProductoRequest $request)
    {
        $data = $request->validated();
        $data['creado_por'] = auth()->id();

        $producto = Producto::create($data);

        return redirect()
            ->route('inventario.productos.show', $producto)
            ->with('success', "Producto '{$producto->descripcion}' creado correctamente.");
    }

    // ── SHOW ─────────────────────────────────────────────────────
    public function show(Producto $producto)
    {
        $producto->load(['categoria', 'unidadMedida', 'cuentaActivo', 'cuentaCostoVenta', 'cuentaIngreso']);

        $stockPorAlmacen = StockActual::with('almacen')
            ->where('producto_id', $producto->id)
            ->get();

        $ultimosMovimientos = MovimientoInventario::with(['almacen', 'creadoPor'])
            ->where('producto_id', $producto->id)
            ->where('estado', 'activo')
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('Inventarios/Productos/Show', [
            'producto'           => $producto,
            'stockPorAlmacen'    => $stockPorAlmacen,
            'ultimosMovimientos' => $ultimosMovimientos,
            'stockTotal'         => $producto->stockTotal(),
        ]);
    }

    // ── EDIT ─────────────────────────────────────────────────────
    public function edit(Producto $producto)
    {
        return Inertia::render('Inventarios/Productos/Edit', [
            'producto'   => $producto,
            'categorias' => CategoriaProducto::activos()->orderBy('nombre')->get(),
            'unidades'   => UnidadMedida::activos()->orderBy('nombre')->get(),
            'cuentas'    => Cuentas::orderBy('codigo')->get(['id', 'codigo', 'nombre', 'tipo']),
        ]);
    }

    // ── UPDATE ───────────────────────────────────────────────────
    public function update(ProductoRequest $request, Producto $producto)
    {
        $data = $request->validated();

        if ($producto->metodo_costeo !== $data['metodo_costeo'] && $producto->movimientos()->exists()) {
            return back()->withErrors([
                'metodo_costeo' => 'No se puede cambiar el método de costeo si el producto ya tiene movimientos registrados.'
            ]);
        }

        $producto->update($data);

        return redirect()
            ->route('inventario.productos.show', $producto)
            ->with('success', 'Producto actualizado correctamente.');
    }

    // ── DESTROY ──────────────────────────────────────────────────
    public function destroy(Producto $producto)
    {
        if ($producto->movimientos()->exists()) {
            $producto->update(['activo' => false]);
            return redirect()->route('inventario.productos.index')
                ->with('success', 'El producto tiene movimientos. Se desactivó en lugar de eliminar.');
        }

        $producto->delete();
        return redirect()->route('inventario.productos.index')
            ->with('success', 'Producto eliminado.');
    }

    // ── KARDEX ───────────────────────────────────────────────────
    public function kardex(Request $request, Producto $producto)
    {
        $request->validate([
            'periodo_id'  => 'nullable|exists:periodos,id',
            'almacen_id'  => 'nullable|exists:almacenes,id',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
        ]);

        $query = MovimientoInventario::with(['almacen', 'almacenDestino', 'creadoPor'])
            ->where('producto_id', $producto->id)
            ->where('estado', 'activo');

        if ($request->filled('periodo_id')) $query->where('periodo_id', $request->periodo_id);
        if ($request->filled('almacen_id')) $query->where('almacen_id', $request->almacen_id);
        if ($request->filled('fecha_desde')) $query->where('fecha', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta')) $query->where('fecha', '<=', $request->fecha_hasta);

        $movimientos = $query->orderBy('fecha')->orderBy('id')->get();

        return Inertia::render('Inventarios/Productos/Kardex', [
            'producto'    => $producto->load('unidadMedida'),
            'movimientos' => $movimientos,
            'almacenes'   => Almacen::activos()->get(['id', 'nombre']),
            'periodos'    => Periodo::orderBy('fecha_inicio', 'desc')->get(['id', 'nombre']),
            'filters'     => $request->only(['periodo_id', 'almacen_id', 'fecha_desde', 'fecha_hasta']),
        ]);
    }

    // ── STOCK VALORIZADO ─────────────────────────────────────────
    public function stockValorizado(Request $request)
    {
        $almacenId = $request->almacen_id;

        $query = StockActual::with(['producto.categoria', 'producto.unidadMedida', 'almacen'])
            ->where('cantidad', '>', 0);

        if ($almacenId) $query->where('almacen_id', $almacenId);

        $stock = $query->orderBy('almacen_id')->get();

        $totalValor = $stock->sum(fn($s) => $s->cantidad * $s->costo_unitario);

        return Inertia::render('Inventarios/Productos/StockValorizado', [
            'stock'     => $stock,
            'totalValor'=> $totalValor,
            'almacenes' => Almacen::activos()->get(['id', 'nombre']),
            'almacenId' => $almacenId,
        ]);
    }

    // ── BÚSQUEDA PARA POS (JSON) ─────────────────────────────────
    public function buscarPos(Request $request)
    {
        $term      = $request->get('q', '');
        $almacenId = $request->get('almacen_id');

        $productos = Producto::activos()
            ->when($term, fn($q) => $q->buscar($term))
            ->with(['unidadMedida', 'stockActual' => function ($q) use ($almacenId) {
                if ($almacenId) $q->where('almacen_id', $almacenId);
            }])
            ->limit(20)
            ->get()
            ->map(fn($p) => [
                'id'           => $p->id,
                'codigo'       => $p->codigo,
                'descripcion'  => $p->descripcion,
                'precio_venta' => (float) $p->precio_venta,
                'aplica_iva'   => $p->aplica_iva,
                'unidad'       => $p->unidadMedida?->codigo ?? $p->unidadMedida?->nombre,
                'stock'        => $almacenId
                    ? (float) ($p->stockActual->first()?->cantidad ?? 0)
                    : $p->stockTotal(),
            ]);

        return response()->json($productos);
    }

    // ── HELPER PRIVADO ───────────────────────────────────────────
    private function contarBajoMinimo(): int
    {
        return StockActual::join('productos', 'productos.id', '=', 'stock_actual.producto_id')
            ->where('productos.stock_minimo', '>', 0)
            ->whereColumn('stock_actual.cantidad', '<', 'productos.stock_minimo')
            ->distinct('stock_actual.producto_id')
            ->count('stock_actual.producto_id');
    }
}
