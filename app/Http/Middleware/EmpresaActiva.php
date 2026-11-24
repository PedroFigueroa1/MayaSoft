<?php
// ARCHIVO: app/Http/Middleware/EmpresaActiva.php
// ------------------------------------------------------------
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EmpresaActiva
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Una consulta por request, a propósito: si le quitan el acceso a una
        // empresa, deja de tenerlo de inmediato (no queda "pegado" en la sesión).
        $ids = $user->empresas()->pluck('empresas.id');

        if ($ids->isEmpty()) {
            abort(403, 'Tu usuario no tiene ninguna empresa asignada. Contacta al administrador.');
        }

        if (!$ids->contains(session('empresa_id'))) {
            $elegida = $ids->count() === 1
                ? $ids->first()
                : ($ids->contains($user->empresa_id) ? $user->empresa_id : null);   // la última usada

            if (!$elegida) {
                return redirect()->route('empresas.seleccionar');
            }
            session(['empresa_id' => $elegida]);
        }

        return $next($request);
    }
}


// ARCHIVO: bootstrap/app.php   (Laravel 11+)   — ¡ORDEN IMPORTANTE!
// ------------------------------------------------------------
// El middleware DEBE correr ANTES de SubstituteBindings (el que resuelve {venta} en la URL).
// Si corre después, Venta $venta se busca sin el filtro de empresa y un usuario podría abrir
// ventas de otra empresa cambiando el ID en la URL.
//
//   ->withMiddleware(function (Middleware $middleware) {
//       $middleware->alias(['empresa' => \App\Http\Middleware\EmpresaActiva::class]);
//       $middleware->prependToPriorityList(
//           before:  \Illuminate\Routing\Middleware\SubstituteBindings::class,
//           prepend: \App\Http\Middleware\EmpresaActiva::class,
//       );
//   })
//
// Laravel 10 o anterior: en app/Http/Kernel.php agrega EmpresaActiva a $middlewarePriority
// justo ANTES de \Illuminate\Routing\Middleware\SubstituteBindings::class.
//
// Después de configurarlo, PRUÉBALO: con la empresa A activa, abre la URL de una venta de la B.
// Debe dar 404.
