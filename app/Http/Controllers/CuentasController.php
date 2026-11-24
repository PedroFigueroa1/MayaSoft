<?php

namespace App\Http\Controllers;

use App\Models\Cuentas;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class CuentasController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $userId = auth()->id();

        $cuentas = Cuentas::query()
            ->with('padre')
            ->visiblePara($userId)
            ->when($request->input('search'), function ($query, $search) {
                $query->where('nombre', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%");
            })
            ->orderBy('codigo')
            ->paginate(100)
            ->withQueryString();

        return Inertia::render('Cuentas/Index', [
            'cuentas' => $cuentas,
            'filters' => $request->only(['search']),
            'tipos'   => Cuentas::TIPOS,
        ]);
    }

    public function create()
    {
        $userId = auth()->id();

        // Solo puede ser padre una cuenta que no permite movimiento
        // y que el usuario pueda ver (suyas + base)
        $cuentasPadre = Cuentas::visiblePara($userId)
            ->where('permite_movimiento', false)
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre', 'es_base', 'user_id']);

        return Inertia::render('Cuentas/Form', [
            'tipos'        => Cuentas::TIPOS,
            'cuentasPadre' => $cuentasPadre,
            'niveles'      => range(1, 5),
        ]);
    }

    public function store(Request $request)
    {
        $userId = auth()->id();

        $validated = $request->validate([
            'codigo'            => [
                'required',
                'string',
                'max:15',
                // Código único solo dentro de las cuentas del usuario
                Rule::unique('cuentas', 'codigo')->where('user_id', $userId),
            ],
            'nombre'            => 'required|string|max:100',
            'tipo'              => 'required|in:A,P,PN,I,G',
            'nivel'             => 'required|integer|min:1|max:5',
            'permite_movimiento'=> 'required|boolean',
            'cuenta_padre_id'   => 'nullable|exists:cuentas,id',
            'descripcion'       => 'nullable|string',
        ]);

        $validated['user_id'] = $userId;
        $validated['es_base'] = false;

        Cuentas::create($validated);

        return redirect()->route('cuentas.index')
            ->with('success', 'Cuenta creada exitosamente');
    }

    public function edit(Cuentas $cuenta)
    {
        // No puede editar cuentas base ni cuentas de otro usuario
        abort_if($cuenta->esDeSoloLectura(), 403, 'No puedes editar una cuenta base');
        abort_if($cuenta->user_id !== auth()->id(), 403, 'No tienes permiso para editar esta cuenta');

        $userId = auth()->id();

        $cuentasPadre = Cuentas::visiblePara($userId)
            ->where('permite_movimiento', false)
            ->where('id', '!=', $cuenta->id)
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre', 'es_base', 'user_id']);

        return Inertia::render('Cuentas/Form', [
            'cuenta'       => $cuenta,
            'tipos'        => Cuentas::TIPOS,
            'cuentasPadre' => $cuentasPadre,
            'niveles'      => range(1, 5),
        ]);
    }
// app/Http/Controllers/CuentasController.php

    public function update(Request $request, Cuentas $cuentas)
    {
        abort_if($cuentas->esDeSoloLectura(), 403, 'No puedes editar una cuenta base');
        abort_if((int)$cuentas->user_id !== (int)auth()->id(), 403, 'No tienes permiso para editar esta cuenta');

        $userId = auth()->id();

        $validated = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:15',
                Rule::unique('cuentas', 'codigo')
                    ->where('user_id', $userId)
                    ->ignore($cuentas->id),
            ],
            'nombre'            => 'required|string|max:100',
            'tipo'              => 'required|in:A,P,PN,I,G',
            'nivel'             => 'required|integer|min:1|max:5',
            'permite_movimiento'=> 'required|boolean',
            'cuenta_padre_id'   => 'nullable|exists:cuentas,id',
            'descripcion'       => 'nullable|string',
        ]);

        $cuentas->update($validated);

        return redirect()->route('cuentas.index')
            ->with('success', 'Cuenta actualizada exitosamente');
    }


    public function destroy(Cuentas $cuenta)
    {
        abort_if($cuenta->esDeSoloLectura(), 403, 'No puedes eliminar una cuenta base');
        abort_if($cuenta->user_id !== auth()->id(), 403, 'No tienes permiso para eliminar esta cuenta');

        if ($cuenta->tieneSubcuentas()) {
            return back()->with('error', 'No se puede eliminar una cuenta que tiene subcuentas');
        }

        $cuenta->delete();

        return redirect()->route('cuentas.index')
            ->with('success', 'Cuenta eliminada exitosamente');
    }
}
