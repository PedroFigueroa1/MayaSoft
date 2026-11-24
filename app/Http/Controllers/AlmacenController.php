<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Cuentas;
use App\Http\Requests\Inventario\AlmacenRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AlmacenController extends Controller
{
    public function index(Request $request)
    {
        $query = Almacen::with('cuenta');

        if ($request->filled('buscar')) {
            $b = $request->input('buscar');
            $query->where(function ($q) use ($b) {
                $q->where('nombre', 'like', "%{$b}%")
                  ->orWhere('codigo', 'like', "%{$b}%")
                  ->orWhere('responsable', 'like', "%{$b}%");
            });
        }

        if ($request->boolean('punto_venta')) {
            $query->where('es_punto_venta', true);
        }

        if ($request->boolean('produccion')) {
            $query->where('es_produccion', true);
        }

        if ($request->has('activo') && $request->input('activo') !== '') {
            $query->where('activo', $request->boolean('activo'));
        }

        return Inertia::render('Inventarios/Almacenes/Index', [
            'items' => $query->orderBy('codigo')->paginate(20)->withQueryString(),
            'filters' => $request->only(['buscar', 'punto_venta', 'produccion', 'activo']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Inventarios/Almacenes/Create', [
            'cuentas' => Cuentas::orderBy('codigo')->get(['id', 'codigo', 'nombre']),
        ]);
    }

    public function store(AlmacenRequest $request)
    {
        Almacen::create($request->validated());
        return redirect()->route('inventario.almacenes.index')->with('success', 'Almacén creado correctamente.');
    }

    public function show(Almacen $almacene)
    {
        return Inertia::render('Inventarios/Almacenes/Show', [
            'item' => $almacene->load('cuenta'),
        ]);
    }

    public function edit(Almacen $almacene)
    {
        return Inertia::render('Inventarios/Almacenes/Edit', [
            'item' => $almacene,
            'cuentas' => Cuentas::orderBy('codigo')->get(['id', 'codigo', 'nombre']),
        ]);
    }

    public function update(AlmacenRequest $request, Almacen $almacene)
    {
        $almacene->update($request->validated());
        return redirect()->route('inventario.almacenes.index')->with('success', 'Almacén actualizado correctamente.');
    }

    public function destroy(Almacen $almacene)
    {
        $almacene->delete();
        return redirect()->route('inventario.almacenes.index')->with('success', 'Almacén eliminado correctamente.');
    }

}
