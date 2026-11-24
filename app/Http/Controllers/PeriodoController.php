<?php

namespace App\Http\Controllers;

use App\Models\Periodo;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PeriodoController extends Controller
{
    public function index(Request $request, Empresa $empresa)
    {
        // Verificar acceso a la empresa primero
        //Gate::authorize('view-empresa', $empresa);
        //$this->authorize('viewAny', Periodo::class);

        $periodos = Periodo::query()
            ->where('empresa_id', $empresa->id)
            ->with(['empresa', 'cerradoPor'])
            ->when($request->input('search'), function ($query, $search) {
                $query->where('nombre', 'like', "%{$search}%")
                    ->orWhereHas('empresa', function ($q) use ($search) {
                        $q->where('nombre', 'like', "%{$search}%");
                    });
            })
            ->orderByDesc('fecha_inicio')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Periodos/Index', [
            'periodos' => $periodos,
            'filters' => $request->only(['search']),
            'empresa' => $empresa,
            'estados' => Periodo::ESTADOS,
            'can' => [
                'create' => auth()->user()->can('create', Periodo::class),
                'cerrar' => auth()->user()->can('cerrar', Periodo::class),
            ]
        ]);
    }

    public function create(Empresa $empresa)
    {
        //$this->authorize('create', [Periodo::class, $empresa]);

        return Inertia::render('Periodos/Form', [
            'empresas' => auth()->user()->empresas,
            'empresaSeleccionada' => $empresa->id,
            'estados' => Periodo::ESTADOS,
        ]);
    }

public function store(Request $request)
{
    $validated = $request->validate([
        'empresa_id' => 'required|exists:empresas,id',
        'nombre' => 'required|string|max:20',
        'fecha_inicio' => 'required|date',
        'fecha_cierre' => 'required|date|after_or_equal:fecha_inicio',
    ]);

    // Verificar unicidad de nombre usando Rule::unique
    $request->validate([
        'nombre' => Rule::unique('periodos')->where(function ($query) use ($request) {
            return $query->where('empresa_id', $request->empresa_id);
        })
    ]);

    Periodo::create($validated);

    return redirect()->route('periodos.index', $request->empresa_id)
        ->with('success', 'Periodo actualizado exitosamente');
}

    public function show(Empresa $empresa, Periodo $periodo)
    {
        //Gate::authorize('view-empresa', $empresa);
        //$this->authorize('view', $periodo);

        $periodo->load([
            'empresa',
            'cerradoPor', // Relación para el usuario que cerró el período
            'asientos' => function ($query) {
                $query->latest('fecha')->limit(1); // Solo el último asiento para la fecha
            }
        ]);

        // Agregar count de asientos
        $periodo->asientos_count = $periodo->asientos()->count();

        return Inertia::render('Periodos/Show', [
            'periodo' => $periodo,
            'empresa' => $empresa,
            'estados' => Periodo::ESTADOS,
        ]);
    }

    public function edit(Empresa $empresa, Periodo $periodo)
    {
        //Gate::authorize('view-empresa', $empresa);
        //$this->authorize('update', $periodo);

        return Inertia::render('Periodos/Form', [
            'periodo' => $periodo,
            'empresas' => auth()->user()->empresas,
            'empresaSeleccionada' => $empresa->id,
            'estados' => Periodo::ESTADOS,
            'isEditing' => true,
        ]);
    }

    public function update(Request $request, Empresa $empresa, Periodo $periodo)
    {
        //Gate::authorize('view-empresa', $empresa);
        //$this->authorize('update', $periodo);

        $validated = $request->validate([
            'nombre' => 'required|string|max:20',
            'fecha_inicio' => 'required|date',
            'fecha_cierre' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        // Verificar unicidad excluyendo el actual
        if (Periodo::where('empresa_id', $empresa->id)
            ->where('nombre', $validated['nombre'])
            ->where('id', '!=', $periodo->id)
            ->exists()) {
            return back()->withErrors([
                'nombre' => 'Ya existe un periodo con este nombre para esta empresa.'
            ]);
        }

        $periodo->update($validated);

        return redirect()->route('periodos.index', $empresa)
            ->with('success', 'Periodo actualizado exitosamente');
    }

    public function destroy(Empresa $empresa, Periodo $periodo)
    {
        //Gate::authorize('view-empresa', $empresa);
        //$this->authorize('delete', $periodo);

        if ($periodo->asientos()->exists()) {
            return back()->with('error', 'No se puede eliminar un periodo con asientos contables');
        }

        $periodo->delete();

        return redirect()->route('periodos.index', $empresa)
            ->with('success', 'Periodo eliminado exitosamente');
    }

    public function cerrar(Empresa $empresa, Periodo $periodo)
    {
       // Gate::authorize('view-empresa', $empresa);
       // $this->authorize('cerrar', $periodo);

        DB::transaction(function () use ($periodo) {
            if (!$periodo->cerrar()) {
                throw new \Exception('No se pudo cerrar el periodo. Verifique que todos los asientos estén balanceados.');
            }
        });

        return redirect()->route('periodos.show', [$empresa, $periodo])
            ->with('success', 'Periodo cerrado exitosamente');
    }
    public function cambiar(Request $request)
    {
        $data = $request->validate([
            'periodo_id' => 'required|integer',
        ]);

        // La empresa activa SIEMPRE sale de la sesión, nunca del request.
        $empresaId = session('empresa_id');

        // Validar que el período pertenece a la empresa activa
        $periodo = Periodo::where('empresa_id', $empresaId)
            ->whereKey($data['periodo_id'])
            ->first();

        abort_unless($periodo, 404, 'Período no encontrado para la empresa activa.');

        // Opcional: no permitir seleccionar un período cerrado
        abort_if($periodo->estado === 'C', 403, 'El período está cerrado.');

        session(['periodo_id' => $periodo->id]);

        return back();
    }
}
