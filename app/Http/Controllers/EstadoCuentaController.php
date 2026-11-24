<?php

namespace App\Http\Controllers;

use App\Models\ProveedorCliente;
use App\Models\MovimientoCuentaCorriente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EstadoCuentaController extends Controller
{
    /**
     * Lista general: todos los clientes/proveedores con saldo
     */
    public function index(Request $request)
    {
        $tipo = $request->get('tipo', 'cliente'); // cliente | proveedor
        $term = $request->get('buscar');

        $query = ProveedorCliente::query()
            ->when($tipo === 'cliente', fn($q) => $q->where('es_cliente', true))
            ->when($tipo === 'proveedor', fn($q) => $q->where('es_proveedor', true))
            ->when($term, fn($q) => $q->buscar($term))
            ->withSum(['movimientosCtaCte as total_cargos' => fn($q) => $q->where('tipo', 'cargo')->where('estado', '!=', 'anulado')], 'monto')
            ->withSum(['movimientosCtaCte as total_abonos' => fn($q) => $q->where('tipo', 'abono')->where('estado', '!=', 'anulado')], 'monto')
            ->withCount(['movimientosCtaCte as docs_pendientes' => fn($q) => $q->where('estado', 'pendiente')]);

        $items = $query->orderBy('razon_social')->paginate(30)->withQueryString();

        // Calcular saldo
        $items->getCollection()->transform(function ($p) {
            $p->saldo = (float) ($p->total_cargos ?? 0) - (float) ($p->total_abonos ?? 0);
            return $p;
        });

        return Inertia::render('Inventarios/ProveedoresClientes/EstadoCuenta/Index', [
            'items'   => $items,
            'tipo'    => $tipo,
            'filters' => $request->only(['buscar', 'tipo']),
            'totales' => [
                'por_cobrar' => $this->totalPorCobrar(),
                'por_pagar'  => $this->totalPorPagar(),
            ],
        ]);
    }

    /**
     * Detalle de estado de cuenta de un proveedor/cliente
     */
    public function show(Request $request, ProveedorCliente $proveedorCliente)
    {
        $desde = $request->get('desde');
        $hasta = $request->get('hasta');

        $query = $proveedorCliente->movimientosCtaCte()
            ->with('creadoPor:id,name')
            ->when($desde, fn($q) => $q->where('fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->where('fecha', '<=', $hasta))
            ->orderBy('fecha')->orderBy('id');

        $movimientos = $query->get();

        // Calcular saldo acumulado fila por fila
        $saldo = 0;
        $movimientos = $movimientos->map(function ($m) use (&$saldo) {
            if ($m->estado !== 'anulado') {
                $saldo += $m->tipo === 'cargo' ? (float) $m->monto : -(float) $m->monto;
            }
            $m->saldo_acumulado = $saldo;
            return $m;
        });

        // Aging (antigüedad de saldos)
        $aging = $this->calcularAging($proveedorCliente);

        return Inertia::render('Inventarios/ProveedoresClientes/EstadoCuenta/Show', [
            'item'        => $proveedorCliente,
            'movimientos' => $movimientos,
            'saldo'       => $saldo,
            'aging'       => $aging,
            'filters'     => $request->only(['desde', 'hasta']),
        ]);
    }

    /**
     * Registrar cargo (factura, nota débito)
     */
    public function storeCargo(Request $request, ProveedorCliente $proveedorCliente)
    {
        $data = $request->validate([
            'fecha'             => 'required|date',
            'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha',
            'concepto'          => 'required|string|max:255',
            'documento_tipo'    => 'nullable|string|max:30',
            'documento_numero'  => 'nullable|string|max:50',
            'monto'             => 'required|numeric|min:0.01',
        ]);

        $proveedorCliente->movimientosCtaCte()->create(array_merge($data, [
            'tipo'   => 'cargo',
            'estado' => 'pendiente',
        ]));

        return back()->with('success', 'Cargo registrado.');
    }

    /**
     * Registrar abono (pago, nota crédito)
     */
    public function storeAbono(Request $request, ProveedorCliente $proveedorCliente)
    {
        $data = $request->validate([
            'fecha'            => 'required|date',
            'concepto'         => 'required|string|max:255',
            'documento_tipo'   => 'nullable|string|max:30',
            'documento_numero' => 'nullable|string|max:50',
            'monto'            => 'required|numeric|min:0.01',
        ]);

        $proveedorCliente->movimientosCtaCte()->create(array_merge($data, [
            'tipo'   => 'abono',
            'estado' => 'pagado',
        ]));

        return back()->with('success', 'Abono registrado.');
    }

    public function destroyMovimiento(MovimientoCuentaCorriente $movimiento)
    {
        $movimiento->update(['estado' => 'anulado']);
        $movimiento->delete();
        return back()->with('success', 'Movimiento anulado.');
    }

    // ── Helpers ───────────────────────────────────────────────
    private function totalPorCobrar(): float
    {
        return (float) DB::table('movimientos_cuenta_corriente as m')
            ->join('proveedores_clientes as p', 'p.id', '=', 'm.proveedor_cliente_id')
            ->where('p.es_cliente', true)
            ->where('m.estado', '!=', 'anulado')
            ->whereNull('m.deleted_at')
            ->selectRaw("SUM(CASE WHEN m.tipo = 'cargo' THEN m.monto ELSE -m.monto END) as total")
            ->value('total') ?? 0;
    }

    private function totalPorPagar(): float
    {
        return abs((float) DB::table('movimientos_cuenta_corriente as m')
            ->join('proveedores_clientes as p', 'p.id', '=', 'm.proveedor_cliente_id')
            ->where('p.es_proveedor', true)
            ->where('m.estado', '!=', 'anulado')
            ->whereNull('m.deleted_at')
            ->selectRaw("SUM(CASE WHEN m.tipo = 'cargo' THEN m.monto ELSE -m.monto END) as total")
            ->value('total') ?? 0);
    }

    /**
     * Aging: agrupa saldos pendientes por rango de días vencidos
     */
    private function calcularAging(ProveedorCliente $pc): array
    {
        $hoy = now();
        $pendientes = $pc->movimientosCtaCte()
            ->where('tipo', 'cargo')
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->where('estado', '!=', 'anulado')
            ->get();

        $buckets = [
            'corriente' => 0,   // no vencido
            'dias_1_30' => 0,
            'dias_31_60'=> 0,
            'dias_61_90'=> 0,
            'dias_90_mas' => 0,
        ];

        foreach ($pendientes as $m) {
            $monto = (float) $m->monto;
            if (!$m->fecha_vencimiento || $m->fecha_vencimiento >= $hoy) {
                $buckets['corriente'] += $monto;
                continue;
            }
            $dias = $m->fecha_vencimiento->diffInDays($hoy);
            if ($dias <= 30)      $buckets['dias_1_30'] += $monto;
            elseif ($dias <= 60)  $buckets['dias_31_60'] += $monto;
            elseif ($dias <= 90)  $buckets['dias_61_90'] += $monto;
            else                  $buckets['dias_90_mas'] += $monto;
        }

        return $buckets;
    }
}