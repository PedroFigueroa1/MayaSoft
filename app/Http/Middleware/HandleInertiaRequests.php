<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        // Empresas a las que el usuario tiene acceso
        $empresasUsuario = [];
        if ($user) {
            $empresasUsuario = $user->empresas()
                ->get(['empresas.id', 'empresas.nombre', 'empresas.nit', 'empresas.logo'])
                ->toArray();
        }

        // Empresa activa según la sesión (la setea el middleware EmpresaActiva)
        $empresaActual = null;
        if ($empresaId = $request->session()->get('empresa_id')) {
            $empresaActual = collect($empresasUsuario)->firstWhere('id', $empresaId);
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => fn () => $user
                    ? [
                        'id'          => $user->id,
                        'name'        => $user->name,
                        'email'       => $user->email,
                        'roles'       => $user->getRoleNames()->toArray(),
                        'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    ]
                    : null,
            ],

            // 👇 NUEVO: empresa activa y empresas del usuario
            'empresaActual'   => $empresaActual,
            'empresasUsuario' => $empresasUsuario,

            'flash' => [
                'success'       => fn () => $request->session()->get('success'),
                'error'         => fn () => $request->session()->get('error'),
                'temp_password' => fn () => $request->session()->get('temp_password'),
            ],
        ]);
    }
}
