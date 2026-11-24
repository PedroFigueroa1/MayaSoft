<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventario\UnidadMedidaRequest;
use App\Http\Resources\Inventario\UnidadMedidaResource;
use App\Models\UnidadMedida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UnidadMedidaController extends Controller
{
    /**
     * Listar todas las unidades de medida
     * GET /api/inventario/unidades-medida
     */
    public function index(Request $request): \Inertia\Response
    {
        $user = $request->user();

        abort_if(! $user->empresa_id, 403, 'El usuario no tiene empresa asignada.');

        $query = UnidadMedida::query()
            ->where('empresa_id', $user->empresa_id);   // ✅ filtro multi-tenant

        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'like', "%{$buscar}%")
                ->orWhere('nombre', 'like', "%{$buscar}%");
            });
        }

        if ($request->boolean('solo_activos')) {
            $query->activos();
        }

        $query->orderBy('codigo');

        $unidades = $query->paginate($request->input('per_page', 15))->withQueryString();

        $items = $unidades->getCollection()
            ->map(fn ($u) => [
                'id'          => $u->id,
                'codigo'      => $u->codigo,
                'nombre'      => $u->nombre,
                'descripcion' => $u->descripcion,
                'activo'      => $u->activo,
            ])
            ->values()
            ->toArray();

        return Inertia::render('Inventarios/UnidadesMedida/Index', [
            'unidades' => $items,
            'filtros'  => $request->only(['buscar', 'solo_activos']),
            'empresa'  => $user->empresa,   // requiere la relación belongsTo en User
        ]);
    }


    /**
     * Crear nueva unidad de medida
     * POST /api/inventario/unidades-medida
     */
    public function store(UnidadMedidaRequest $request)
    {
        $user = $request->user();

        if (! $user->empresa_id) {
            abort(403, 'El usuario no tiene empresa asignada.');
        }

        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['empresa_id'] = $user->empresa_id;

            UnidadMedida::create($data);

            DB::commit();

            return redirect()
                ->route('inventario.unidades-medida.index')
                ->with('success', 'Unidad de medida creada correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors([
                'general' => 'Error al crear la unidad: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Ver una unidad de medida
     * GET /api/inventario/unidades-medida/{id}
     */
    public function show(UnidadMedida $unidadMedida): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UnidadMedidaResource($unidadMedida->loadCount('productos')),
        ]);
    }

    /**
     * Actualizar unidad de medida
     * PUT /api/inventario/unidades-medida/{id}
     */
    public function update(UnidadMedidaRequest $request, UnidadMedida $unidadMedida)
    {
        // 🔒 Verificar que pertenezca a la empresa del usuario
        abort_if($unidadMedida->empresa_id !== $request->user()->empresa_id, 403);

        try {
            DB::beginTransaction();

            $unidadMedida->update($request->validated());

            DB::commit();

            return redirect()
                ->route('inventario.unidades-medida.index')
                ->with('success', 'Unidad de medida actualizada correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors([
                'general' => 'Error al actualizar la unidad: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Eliminar (soft delete) unidad de medida
     * DELETE /api/inventario/unidades-medida/{id}
     */
    public function destroy(UnidadMedida $unidadMedida): JsonResponse
    {
        // Validar que no tenga productos asociados
        if ($unidadMedida->productos()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la unidad porque tiene productos asociados. Desactívela en su lugar.',
            ], 422);
        }

        try {
            $unidadMedida->delete();

            return response()->json([
                'success' => true,
                'message' => 'Unidad de medida eliminada correctamente.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la unidad de medida.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activar/Desactivar unidad de medida
     * PATCH /api/inventario/unidades-medida/{id}/toggle
     */
    public function toggleActivo(UnidadMedida $unidadMedida): JsonResponse
    {
        $unidadMedida->activo = !$unidadMedida->activo;
        $unidadMedida->save();

        $estado = $unidadMedida->activo ? 'activada' : 'desactivada';

        return response()->json([
            'success' => true,
            'message' => "Unidad de medida {$estado} correctamente.",
            'data' => new UnidadMedidaResource($unidadMedida),
        ]);
    }
}
