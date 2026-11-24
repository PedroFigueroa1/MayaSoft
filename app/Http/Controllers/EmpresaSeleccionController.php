<?php

// ARCHIVO: app/Http/Controllers/EmpresaSeleccionController.php
// ------------------------------------------------------------
namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class EmpresaSeleccionController extends Controller
{
    public function seleccionar(Request $request)
    {
        $empresas = $request->user()->empresas()
            ->orderBy('empresas.nombre')
            ->get(['empresas.id', 'empresas.nombre', 'empresas.nit', 'empresas.ciudad', 'empresas.logo']);

        if ($empresas->count() === 1) {
            return $this->activar($request, $empresas->first()->id);
        }

        return Inertia::render('Empresa/Seleccionar', [
            'empresas' => $empresas,
            'actual'   => session('empresa_id'),
        ]);
    }

    public function cambiar(Request $request)
    {
        $request->validate(['empresa_id' => 'required|integer']);
        return $this->activar($request, (int) $request->empresa_id);
    }

    private function activar(Request $request, int $empresaId)
    {
        // Seguridad: solo empresas que realmente pertenecen al usuario
        $empresa = $request->user()->empresas()
            ->where('empresas.id', $empresaId)->firstOrFail();

        session(['empresa_id' => $empresa->id]);
        $request->user()->forceFill(['empresa_id' => $empresa->id])->save();   // "última usada"

        // Al dashboard, NO back(): las URLs de la empresa anterior darían 404
        return redirect()->route('dashboard')->with('success', "Trabajando en {$empresa->nombre}.");
    }
}

