<?php

namespace App\Http\Controllers;

use App\Models\CentroCosto;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class CentroCostoController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $userId = auth()->id();

        $centros = CentroCosto::visiblePara($userId)
            ->when($request->input('search'), fn($q, $s) =>
                $q->where('nombre', 'like', "%{$s}%")
                  ->orWhere('codigo', 'like', "%{$s}%")
            )
            ->orderBy('codigo')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Asientos/Centro-costos', [
            'centros'  => $centros,
            'filters'  => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('CentroCostos/Form');
    }

    public function store(Request $request)
    {
        $userId = auth()->id();

        $validated = $request->validate([
            'codigo'      => [
                'required', 'string', 'max:20',
                Rule::unique('centro_costos', 'codigo')->where('user_id', $userId),
            ],
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'activo'      => 'boolean',
        ]);

        $validated['user_id'] = $userId;
        $validated['es_base'] = false;

        CentroCosto::create($validated);

        return redirect()->route('centros-costo.index')
            ->with('success', 'Centro de costo creado exitosamente');
    }

    public function edit(CentroCosto $centroCosto)
    {
        abort_if($centroCosto->esDeSoloLectura(), 403, 'No puedes editar un centro de costo base');
        abort_if((int)$centroCosto->user_id !== (int)auth()->id(), 403, 'No tienes permiso');

        return Inertia::render('CentroCostos/Form', [
            'centro' => $centroCosto,
        ]);
    }

    public function update(Request $request, CentroCosto $centroCosto)
    {
        abort_if($centroCosto->esDeSoloLectura(), 403, 'No puedes editar un centro de costo base');
        abort_if($centroCosto->user_id !== auth()->id(), 403, 'No tienes permiso para editar');

        $userId = auth()->id();

        $validated = $request->validate([
            'codigo' => [
                'required', 'string', 'max:20',
                Rule::unique('centro_costos', 'codigo')
                    ->where('user_id', $userId)
                    ->ignore($centroCosto->id),
            ],
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'activo'      => 'boolean',
        ]);

        $centroCosto->update($validated);

        return redirect()->route('centros-costo.index')
            ->with('success', 'Centro de costo actualizado exitosamente');
    }

    public function destroy(CentroCosto $centroCosto)
    {
        abort_if($centroCosto->esDeSoloLectura(), 403, 'No puedes eliminar un centro de costo base');
        abort_if((int)$centroCosto->user_id !== (int)auth()->id(), 403, 'No tienes permiso');

        $centroCosto->delete();

        return redirect()->route('centros-costo.index')
            ->with('success', 'Centro de costo eliminado exitosamente');
    }

    // ==========================================
    // IMPORTAR DESDE BASE
    // ==========================================

    public function vistaImportar()
    {
        $centrosBase = CentroCosto::base()
            ->activos()
            ->orderBy('codigo')
            ->get();

        return Inertia::render('CentroCostos/Importar', [
            'centrosBase' => $centrosBase,
        ]);
    }

    public function importarDesdeBase(Request $request)
    {
        $request->validate([
            'centro_ids'   => 'required|array|min:1',
            'centro_ids.*' => 'exists:centro_costos,id',
        ]);

        $userId     = auth()->id();
        $importados = 0;
        $omitidos   = 0;

        DB::transaction(function () use ($request, $userId, &$importados, &$omitidos) {

            $centrosBase = CentroCosto::whereIn('id', $request->centro_ids)
                ->where('es_base', true)
                ->get();

            foreach ($centrosBase as $base) {
                $yaExiste = CentroCosto::where('user_id', $userId)
                    ->where('codigo', $base->codigo)
                    ->exists();

                if ($yaExiste) {
                    $omitidos++;
                    continue;
                }

                CentroCosto::create([
                    'user_id'     => $userId,
                    'codigo'      => $base->codigo,
                    'nombre'      => $base->nombre,
                    'descripcion' => $base->descripcion,
                    'activo'      => $base->activo,
                    'es_base'     => false,
                ]);

                $importados++;
            }
        });

        $mensaje = "{$importados} centro(s) importado(s) correctamente";
        if ($omitidos > 0) {
            $mensaje .= ", {$omitidos} omitido(s) por código duplicado";
        }

        return redirect()->route('centros-costo.index')
            ->with('success', $mensaje);
    }
}
