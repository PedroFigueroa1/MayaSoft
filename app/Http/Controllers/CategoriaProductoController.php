<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventario\CategoriaProductoRequest;
use App\Http\Resources\Inventario\CategoriaProductoResource;
use App\Models\CategoriaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CategoriaProductoController extends Controller
{
    /**
     * Listar categorías (árbol o lista plana)
     * GET /api/inventario/categorias
     */

    // ─── LISTADO CON FILTROS ───
public function index(Request $request)
{
    $empresa   = $request->user()->empresa;
    $empresaId = $empresa?->id;

    // Siempre enviar empresa, arbol y categoriasPlanas
    return Inertia::render('Inventarios/Categoria/Index', [
        'arbol'           => CategoriaProducto::arbol($empresaId),
        'categoriasPlanas'=> CategoriaProducto::listaPlana($empresaId),
        'empresa'         => $empresa,
    ]);
}
    /**
     * Crear categoría
     * POST /api/inventario/categorias
     */
    public function store(CategoriaProductoRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['empresa_id'] = $request->user()->empresa_id; // Asignar empresa

            $categoria = CategoriaProducto::create($data);

            DB::commit();

            return redirect()->route('inventario.categorias.index')->with('success', 'Categoría creada correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la categoría.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Ver categoría
     * GET /api/inventario/categorias/{id}
     */
    public function show(CategoriaProducto $categoriaProducto): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new CategoriaProductoResource(
                $categoriaProducto->load(['padre', 'hijos', 'descendientes'])
                    ->loadCount('productos')
            ),
        ]);
    }

    /**
     * Actualizar categoría
     * PUT /api/inventario/categorias/{id}
     */
    public function update(CategoriaProductoRequest $request, CategoriaProducto $categoriaProducto): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Validar que no se asigne como padre uno de sus descendientes
            if ($request->filled('parent_id')) {
                $nuevoPadreId = $request->input('parent_id');
                if ($this->esDescendiente($categoriaProducto->id, $nuevoPadreId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No puede asignar como padre una categoría descendiente.',
                    ], 422);
                }
            }

            $categoriaProducto->update($request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada correctamente.',
                'data' => new CategoriaProductoResource($categoriaProducto),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la categoría.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar categoría
     * DELETE /api/inventario/categorias/{id}
     */
    public function destroy(CategoriaProducto $categoriaProducto): JsonResponse
    {
        // Validar que no tenga productos
        if ($categoriaProducto->productos()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la categoría porque tiene productos asociados.',
            ], 422);
        }

        // Validar que no tenga subcategorías
        if ($categoriaProducto->hijos()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la categoría porque tiene subcategorías. Elimínelas primero.',
            ], 422);
        }

        try {
            $categoriaProducto->delete();

            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada correctamente.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la categoría.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activar/Desactivar categoría
     * PATCH /api/inventario/categorias/{id}/toggle
     */
    public function toggleActivo(CategoriaProducto $categoriaProducto): JsonResponse
    {
        $categoriaProducto->activo = !$categoriaProducto->activo;
        $categoriaProducto->save();

        $estado = $categoriaProducto->activo ? 'activada' : 'desactivada';

        return response()->json([
            'success' => true,
            'message' => "Categoría {$estado} correctamente.",
            'data' => new CategoriaProductoResource($categoriaProducto),
        ]);
    }

    /**
     * Verificar si un ID es descendiente de otro
     */
    private function esDescendiente(int $categoriaId, int $posibleDescendienteId): bool
    {
        $categoria = CategoriaProducto::find($posibleDescendienteId);
        if (!$categoria) return false;

        $actual = $categoria->parent_id;
        while ($actual !== null) {
            if ($actual == $categoriaId) return true;
            $padre = CategoriaProducto::find($actual);
            $actual = $padre?->parent_id;
        }
        return false;
    }
}
