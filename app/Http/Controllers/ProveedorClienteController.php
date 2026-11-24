<?php

namespace App\Http\Controllers;

use App\Models\ProveedorCliente;
use App\Models\Cuentas;
use App\Http\Requests\Inventario\ProveedorClienteStoreRequest;
use App\Http\Requests\Inventario\ProveedorClienteUpdateRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProveedorClienteController extends Controller
{
public function index(Request $request)
{
    $query = ProveedorCliente::query()
        ->withSum(['movimientosCtaCte as total_cargos' => fn($q) => $q->where('tipo','cargo')->where('estado','!=','anulado')], 'monto')
        ->withSum(['movimientosCtaCte as total_abonos' => fn($q) => $q->where('tipo','abono')->where('estado','!=','anulado')], 'monto');

    if ($request->filled('buscar')) {
        $b = $request->input('buscar');

        $query->where(fn($q) =>
            $q->where('razon_social', 'like', "%{$b}%")
              ->orWhere('nombre_comercial', 'like', "%{$b}%")
              ->orWhere('nit', 'like', "%{$b}%")
              ->orWhere('ci', 'like', "%{$b}%")
        );
    }

    if ($request->boolean('solo_proveedores')) {
        $query->where('es_proveedor', true);
    }

    if ($request->boolean('solo_clientes')) {
        $query->where('es_cliente', true);
    }

    if ($request->has('activo') && $request->input('activo') !== '') {
        $query->where('activo', $request->boolean('activo'));
    }

    $items = $query->orderBy('razon_social')->paginate(20)->withQueryString();

    $items->getCollection()->transform(function ($p) {
        $p->saldo = (float)($p->total_cargos ?? 0) - (float)($p->total_abonos ?? 0);
        return $p;
    });

    return Inertia::render('Inventarios/ProveedoresClientes/Index', [
        'items'   => $items,
        'filters' => $request->only(['buscar','solo_proveedores','solo_clientes','activo']),
    ]);
}


    public function create()
    {
        return Inertia::render('Inventarios/ProveedoresClientes/Create', [
            'cuentas' => Cuentas::orderBy('codigo')->get(['id', 'codigo', 'nombre']),
        ]);
    }

    public function store(ProveedorClienteStoreRequest $request)
    {
        $user = $request->user();

        if (! $user->empresa_id) {
            return back()->withErrors([
                'general' => 'El usuario no tiene empresa asignada.',
            ]);
        }

        $data = $request->validated();
        $data['empresa_id'] = $user->empresa_id;   // ✅ fuerza la empresa del usuario

        ProveedorCliente::create($data);

        return redirect()
            ->route('inventario.proveedores-clientes.index')
            ->with('success', 'Registro creado correctamente.');
    }

    public function show(ProveedorCliente $proveedorCliente)
    {
        return Inertia::render('Inventarios/ProveedoresClientes/Show', [
            'item' => $proveedorCliente->load(['cuentaPorCobrar', 'cuentaPorPagar']),
        ]);
    }

    public function edit(ProveedorCliente $proveedorCliente)
    {
        return Inertia::render('Inventarios/ProveedoresClientes/Edit', [
            'item' => $proveedorCliente,
            'cuentas' => Cuentas::orderBy('codigo')->get(['id', 'codigo', 'nombre']),
        ]);
    }

    public function update(ProveedorClienteUpdateRequest $request, ProveedorCliente $proveedorCliente)
    {
        // 🔒 Verificar que el registro pertenezca a la empresa del usuario
        abort_if(
            $proveedorCliente->empresa_id !== $request->user()->empresa_id,
            403
        );

        $proveedorCliente->update($request->validated());

        return redirect()
            ->route('inventario.proveedores-clientes.index')
            ->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(ProveedorCliente $proveedorCliente)
    {
        $proveedorCliente->delete();
        return redirect()->route('proveedores-clientes.index')->with('success', 'Registro eliminado correctamente.');
    }
    public function storeExpress(Request $request)
    {
        $request->validate([
            'razon_social' => 'required|string|max:200',
            'nit'          => 'nullable|string|max:20',
            'ci'           => 'nullable|string|max:15',
        ]);

        $data = $request->all();
        $data['empresa_id'] = auth()->user()->empresa_id;
        $data['es_cliente'] = true;
        $data['es_proveedor'] = false;
        $data['activo'] = true;

        $cliente = ProveedorCliente::create($data);

        return response()->json([
            'success' => true,
            'cliente' => [
                'id'           => $cliente->id,
                'razon_social' => $cliente->razon_social,
                'nit'          => $cliente->nit,
                'ci'           => $cliente->ci,
                'complemento'  => $cliente->complemento ?? '',
                'dias_credito' => 0,
                'cuenta_por_cobrar_id' => null,
            ]
        ]);
    }
}
