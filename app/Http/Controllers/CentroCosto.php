<?php

namespace App\Http\Controllers;

use App\Models\CentroCosto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CentroCostoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Si es una petición AJAX para DataTables
        if ($request->ajax()) {
            return $this->getDataTables($request);
        }

        // Para peticiones normales, retornar la vista
        return view('centro-costos.index');
    }

    /**
     * Obtener datos para DataTables
     */
    private function getDataTables(Request $request)
    {
        $query = CentroCosto::query();

        // Búsqueda
        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $orderColumn = $request->order[0]['column'] ?? 0;
        $orderDir = $request->order[0]['dir'] ?? 'asc';
        $columns = ['codigo', 'nombre', 'descripcion', 'activo', 'created_at'];

        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        // Paginación
        $total = $query->count();
        $limit = $request->length ?? 10;
        $offset = $request->start ?? 0;

        $centros = $query->skip($offset)
                        ->take($limit)
                        ->get();

        $data = [];
        foreach ($centros as $centro) {
            $data[] = [
                'id' => $centro->id,
                'codigo' => $centro->codigo,
                'nombre' => $centro->nombre,
                'descripcion' => $centro->descripcion,
                'estado' => $centro->activo ?
                    '<span class="badge bg-success">Activo</span>' :
                    '<span class="badge bg-danger">Inactivo</span>',
                'creado' => $centro->created_at->format('d/m/Y'),
                'acciones' => $this->getAcciones($centro->id)
            ];
        }

        return response()->json([
            'draw' => $request->draw ?? 1,
            'recordsTotal' => CentroCosto::count(),
            'recordsFiltered' => $total,
            'data' => $data
        ]);
    }

    /**
     * Generar HTML de acciones
     */
    private function getAcciones($id)
    {
        return '
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-primary btn-editar"
                        data-id="'.$id.'" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-sm btn-danger btn-eliminar"
                        data-id="'.$id.'" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        ';
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar datos
        $validator = Validator::make($request->all(), [
            'codigo' => 'required|string|max:20|unique:centro_costos,codigo',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $centro = CentroCosto::create([
                'codigo' => strtoupper($request->codigo),
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'activo' => $request->activo ?? true
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Centro de costo creado exitosamente',
                'data' => $centro
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear centro de costo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $centro = CentroCosto::find($id);

        if (!$centro) {
            return response()->json([
                'success' => false,
                'message' => 'Centro de costo no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $centro
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $centro = CentroCosto::find($id);

        if (!$centro) {
            return response()->json([
                'success' => false,
                'message' => 'Centro de costo no encontrado'
            ], 404);
        }

        // Validar datos
        $validator = Validator::make($request->all(), [
            'codigo' => 'required|string|max:20|unique:centro_costos,codigo,'.$id,
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $centro->update([
                'codigo' => strtoupper($request->codigo),
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'activo' => $request->activo ?? true
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Centro de costo actualizado exitosamente',
                'data' => $centro
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar centro de costo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $centro = CentroCosto::find($id);

        if (!$centro) {
            return response()->json([
                'success' => false,
                'message' => 'Centro de costo no encontrado'
            ], 404);
        }

        try {
            // Verificar si tiene registros relacionados antes de eliminar
            $tieneRelaciones = $this->tieneRelaciones($centro);

            if ($tieneRelaciones) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el centro de costo porque tiene registros relacionados'
                ], 400);
            }

            $centro->delete();

            return response()->json([
                'success' => true,
                'message' => 'Centro de costo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar centro de costo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar si el centro de costo tiene registros relacionados
     */
    private function tieneRelaciones($centro)
    {
        // Aquí puedes agregar verificación de relaciones con otros modelos
        // Por ejemplo, si tienes una tabla detalle_asientos que usa centro_costos_id
        return false; // Cambiar según tu estructura de datos
    }

    /**
     * Obtener lista de centros de costo para select
     */
    public function getSelectOptions(Request $request)
    {
        $query = CentroCosto::query();

        if ($request->has('activo') && $request->activo) {
            $query->activo();
        }

        if ($request->has('search') && $request->search) {
            $query->buscar($request->search);
        }

        $centros = $query->orderBy('codigo')->get();

        $options = $centros->map(function ($centro) {
            return [
                'id' => $centro->id,
                'text' => "{$centro->codigo} - {$centro->nombre}"
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $options
        ]);
    }
}
