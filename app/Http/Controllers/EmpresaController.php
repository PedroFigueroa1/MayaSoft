<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmpresaController extends Controller
{
    use AuthorizesRequests;

    // Datos estáticos para reutilización
    protected $monedas = ['BOB', 'USD'];
    protected $departamentos = [
        'La Paz', 'Cochabamba', 'Santa Cruz', 'Oruro',
        'Potosí', 'Tarija', 'Chuquisaca', 'Beni', 'Pando'
    ];

    public function index()
    {
        $this->authorize('viewAny', Empresa::class);

        $actualId = session('empresa_id');

        // La empresa activa primero, luego por nombre
        $empresas = Empresa::forUser(auth()->user())
            ->get()
            ->sortBy(fn ($e) => [$e->id === $actualId ? 0 : 1, $e->nombre])
            ->values();

        return Inertia::render('Empresa/Index', [
            'empresas' => $empresas,
            'actual'   => $actualId ? (int) $actualId : null,
            'can' => [
                'create' => auth()->user()->can('create', Empresa::class),
                'delete' => auth()->user()->can('delete', Empresa::class),
            ],
        ]);
    }


    public function create()
    {
        $this->authorize('create', Empresa::class);

        return Inertia::render('Empresa/Form', [
            'monedas' => ['BOB', 'USD'],
            'departamentos' => $this->departamentos,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Empresa::class);

        $validated = $this->validateRequest($request);

        try {
            if ($request->hasFile('logo')) {
                $validated['logo'] = $this->storeLogo($request);
            }

            $validated['user_id'] = auth()->id();
            Empresa::create($validated);

            Log::info('Empresa creada', ['user_id' => auth()->id(), 'empresa' => $validated['nombre']]);

            return redirect()
                ->route('empresas.index')
                ->with('success', 'Empresa creada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al crear empresa', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->with('error', 'Error al crear la empresa')
                ->withInput();
        }
    }

    public function show(Empresa $empresa)
    {
        $this->authorize('view', $empresa);

        return Inertia::render('Empresa/Show', [
            'empresa' => $empresa->load('user'),
        ]);
    }

    public function edit(Empresa $empresa)
    {
        $this->authorize('update', $empresa);

        return Inertia::render('Empresa/Form', [
            'empresa' => $empresa,
            'monedas' => ['BOB', 'USD'],
            'departamentos' => $this->departamentos,
            'isEditing' => true,
        ]);
    }

    public function update(Request $request, Empresa $empresa)
    {
        $this->authorize('update', $empresa);

        // Debug: Ver qué datos llegan
        \Log::debug('Datos recibidos en update:', $request->all());
        \Log::debug('Archivo logo:', ['has_file' => $request->hasFile('logo')]);

        // Validar los datos
        $validated = $this->validateRequest($request, $empresa);

        try {
            // Manejar el logo si se envió uno nuevo
            if ($request->hasFile('logo')) {
                $this->deleteOldLogo($empresa);
                $validated['logo'] = $this->storeLogo($request);
            } else {
                // Mantener el logo existente si no se envió uno nuevo
                unset($validated['logo']);
            }

            // Actualizar la empresa
            $empresa->update($validated);

            Log::info('Empresa actualizada', [
                'user_id' => auth()->id(),
                'empresa_id' => $empresa->id
            ]);

            return redirect()
                ->route('empresas.index')
                ->with('success', 'Empresa actualizada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al actualizar empresa', [
                'error' => $e->getMessage(),
                'empresa_id' => $empresa->id
            ]);

            return back()
                ->with('error', 'Error al actualizar la empresa: ' . $e->getMessage())
                ->withInput();
        }
    }
    public function destroy(Empresa $empresa)
    {
        $this->authorize('delete', $empresa);

        try {
            $this->deleteOldLogo($empresa);
            $empresa->delete();

            Log::info('Empresa eliminada', [
                'user_id' => auth()->id(),
                'empresa_id' => $empresa->id
            ]);

            return redirect()
                ->route('empresas.index')
                ->with('success', 'Empresa eliminada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al eliminar empresa', [
                'error' => $e->getMessage(),
                'empresa_id' => $empresa->id
            ]);

            return back()
                ->with('error', 'Error al eliminar la empresa');
        }
    }

    // Métodos auxiliares privados
private function validateRequest(Request $request, Empresa $empresa = null)
{
    $rules = [
        'nombre' => 'required|string|max:100',
        'direccion' => 'required|string|max:255',
        'telefono' => 'required|string|max:20',
        'moneda' => 'required|in:BOB,USD',
        'actividad_economica' => 'required|string|max:100',
        'ciudad' => 'required|string|max:50',
        'departamento' => 'required|string|max:50',
        'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ];

    // Regla para NIT
    if ($empresa && $empresa->id) {
        $rules['nit'] = 'required|string|max:15|unique:empresas,nit,' . $empresa->id;
    } else {
        $rules['nit'] = 'required|string|max:15|unique:empresas';
    }

    // Validar y retornar
    return $request->validate($rules, [
        'nombre.required' => 'El nombre es obligatorio',
        'nit.required' => 'El NIT es obligatorio',
        'nit.unique' => 'Este NIT ya está registrado',
        'direccion.required' => 'La dirección es obligatoria',
        'telefono.required' => 'El teléfono es obligatorio',
        'moneda.required' => 'La moneda es obligatoria',
        'departamento.required' => 'El departamento es obligatorio',
        'ciudad.required' => 'La ciudad es obligatoria',
        'actividad_economica.required' => 'La actividad económica es obligatoria',
    ]);
}

    private function storeLogo(Request $request)
    {
        $file = $request->file('logo');
        $nombreEmpresa = Str::slug($request->input('nombre'));
        $fecha = now()->format('Y-m-d_His');
        $extension = $file->getClientOriginalExtension();
        $filename = "{$nombreEmpresa}_{$fecha}.{$extension}";

        return $file->storeAs('logos', $filename, 'public');
    }

    private function deleteOldLogo(Empresa $empresa)
    {
        if ($empresa->logo && Storage::disk('public')->exists($empresa->logo)) {
            Storage::disk('public')->delete($empresa->logo);
        }
    }
    public function logo($ruta)
    {
        // Verificar autenticación (middleware 'auth' ya lo hará, pero lo dejamos por seguridad)
        if (!auth()->check()) {
            abort(403, 'No autorizado');
        }

        // Validar que la ruta no contenga caracteres peligrosos (seguridad básica)
        $ruta = trim($ruta, '/');
        if (strpos($ruta, '..') !== false || strpos($ruta, '/') === 0) {
            abort(404);
        }

        // Construir ruta completa en disco 'public' (porque guardaste en 'public')
        $fullPath = Storage::disk('public')->path($ruta);

        // Verificar existencia
        if (!Storage::disk('public')->exists($ruta)) {
            abort(404, 'Logo no encontrado');
        }

        // Devolver la imagen
        return response()->file($fullPath);
    }
}
