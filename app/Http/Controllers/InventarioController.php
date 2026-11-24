<?php
// app/Http/Controllers/InventarioController.php

namespace App\Http\Controllers;

use App\Models\Periodo;
use Inertia\Inertia;

class InventarioController extends Controller
{
    public function index()
    {
        $empresaId = session('empresa_id');

        $periodos = Periodo::where('empresa_id', $empresaId)
            ->orderByDesc('fecha_inicio')
            ->get(['id', 'nombre', 'fecha_inicio', 'fecha_cierre', 'estado']);

        // Valida que el período en sesión pertenezca a esta empresa
        $periodoId = session('periodo_id');
        $periodoActivo = $periodos->firstWhere('id', $periodoId);

        return Inertia::render('Inventarios/Index', [
            'periodos'      => $periodos,
            'periodoActivo' => $periodoActivo,
        ]);
    }
}
