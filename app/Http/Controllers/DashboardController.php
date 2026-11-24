<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\User;
use App\Models\Periodo;
use App\Models\Asiento;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // 1) SUPERADMIN: dashboard global
        if ($user->hasRole('SUPERADMIN')) {
            return $this->superAdminDashboard();
        }

        // 2) Usuarios sin ningún rol -> "en revisión"
        if ($user->roles->isEmpty()) {
            return Inertia::render('DashboardPending', [
                'mensaje' => 'Tu cuenta está en proceso de revisión. Una vez aprobada, podrás acceder a todas las funcionalidades.',
                'usuario' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at->format('d/m/Y')
                ]
            ]);
        }

        // 3) Usuarios con rol (ADMIN, CONTADOR, etc.)
        //    Obtener sus empresas (relación many-to-many)
        $empresas = $user->empresas;

        if ($empresas->isEmpty()) {
            return Inertia::render('DashboardUser', [
                'sinEmpresa' => true,
                'mensaje' => 'Tienes un rol asignado, pero aún no estás vinculado a ninguna empresa. Crea una empresa o contacta al superadministrador.'
            ]);
        }

        // Tomamos la primera empresa (puedes ajustar la lógica si el usuario tiene varias)
        $empresa = $empresas->first();

        // Períodos de la empresa
        $periodos = Periodo::where('empresa_id', $empresa->id)->get();
        $periodosIds = $periodos->pluck('id');

        // Totales y estadísticas de asientos (solo si existen períodos)
        $totalAsientos = 0;
        $porcentajeAsientos = 0;
        $tendenciaAsientos = 'up';
        $asientosRecientes = [];

        if ($periodosIds->isNotEmpty()) {
            $totalAsientos = Asiento::whereIn('periodo_id', $periodosIds)->count();

            // Asientos recientes (últimos 5)
            $asientosRecientes = Asiento::with('creadoPor')
                ->whereIn('periodo_id', $periodosIds)
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($asiento) {
                    return [
                        'id' => $asiento->id,
                        'fecha' => $asiento->fecha->format('d/m/Y'),
                        'glosa' => $asiento->glosa,
                        'tipo' => $asiento->tipo,
                        'creado_por' => $asiento->creadoPor->name ?? 'Sistema',
                    ];
                });

            // Comparación mes actual vs anterior
            $asientosMesActual = Asiento::whereIn('periodo_id', $periodosIds)
                ->whereYear('fecha', now()->year)
                ->whereMonth('fecha', now()->month)
                ->count();
            $asientosMesPasado = Asiento::whereIn('periodo_id', $periodosIds)
                ->whereYear('fecha', now()->subMonth()->year)
                ->whereMonth('fecha', now()->subMonth()->month)
                ->count();

            if ($asientosMesPasado > 0) {
                $porcentajeAsientos = (($asientosMesActual - $asientosMesPasado) / $asientosMesPasado) * 100;
            } elseif ($asientosMesActual > 0) {
                $porcentajeAsientos = 100;
            }
            $tendenciaAsientos = $porcentajeAsientos >= 0 ? 'up' : 'down';
        }
        $tiposCambio = $this->getExchangeRates();
        return Inertia::render('DashboardUser', [
            'empresa' => [
                'id' => $empresa->id,
                'nombre' => $empresa->nombre,
                'ruc' => $empresa->ruc ?? null,
            ],
            'tienePeriodos' => $periodosIds->isNotEmpty(),
            'primerPeriodoId' => $periodosIds->first(), // útil para enlaces
            'totalAsientos' => $totalAsientos,
            'porcentajeAsientos' => round(abs($porcentajeAsientos), 1),
            'tendenciaAsientos' => $tendenciaAsientos,
            'asientosRecientes' => $asientosRecientes,
            'tiposCambio' => $tiposCambio,
        ]);
    }

    private function superAdminDashboard()
    {
        $totalEmpresas = Empresa::count();
        $totalUsuarios = User::count();
        $totalReportes = Asiento::count();

        // Porcentaje nuevas empresas
        $empresasMesActual = Empresa::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count();
        $empresasMesPasado = Empresa::whereYear('created_at', now()->subMonth()->year)->whereMonth('created_at', now()->subMonth()->month)->count();
        $porcentajeEmpresas = $empresasMesPasado > 0 ? (($empresasMesActual - $empresasMesPasado) / $empresasMesPasado) * 100 : ($empresasMesActual > 0 ? 100 : 0);

        // Porcentaje nuevos usuarios
        $usuariosMesActual = User::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count();
        $usuariosMesPasado = User::whereYear('created_at', now()->subMonth()->year)->whereMonth('created_at', now()->subMonth()->month)->count();
        $porcentajeUsuarios = $usuariosMesPasado > 0 ? (($usuariosMesActual - $usuariosMesPasado) / $usuariosMesPasado) * 100 : ($usuariosMesActual > 0 ? 100 : 0);

        // Usuarios recientes
        $usuariosRecientes = User::with(['empresas' => function ($q) {
            $q->select('id', 'nombre', 'user_id');
        }])->select('id', 'name', 'email', 'created_at')->latest()->take(5)->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at->format('d/m/Y H:i'),
                'empresa_principal' => $user->empresas->first() ? $user->empresas->first()->nombre : 'Sin empresa'
            ];
        });

        // Actividad reciente
        $actividadReciente = Asiento::with(['periodo.empresa', 'creadoPor'])
            ->select('id', 'fecha', 'glosa', 'tipo', 'created_at', 'periodo_id', 'creado_por')
            ->latest()->take(5)->get()->map(function ($asiento) {
                return [
                    'id' => $asiento->id,
                    'fecha' => $asiento->fecha->format('d/m/Y'),
                    'glosa' => $asiento->glosa,
                    'tipo' => $asiento->tipo,
                    'created_at' => $asiento->created_at->format('d/m/Y H:i'),
                    'empresa' => $asiento->periodo->empresa->nombre ?? 'Sin empresa',
                    'creado_por' => $asiento->creadoPor->name ?? 'Sistema'
                ];
            });
        $tiposCambio = $this->getExchangeRates();
        return Inertia::render('Dashboard', [
            'stats' => [
                'empresas' => [
                    'total' => $totalEmpresas,
                    'porcentaje' => round($porcentajeEmpresas, 1),
                    'tendencia' => $porcentajeEmpresas >= 0 ? 'up' : 'down'
                ],
                'usuarios' => [
                    'total' => $totalUsuarios,
                    'porcentaje' => round($porcentajeUsuarios, 1),
                    'tendencia' => $porcentajeUsuarios >= 0 ? 'up' : 'down'
                ],
                'reportes' => [
                    'total' => $totalReportes,
                    'porcentaje' => -3.0,
                    'tendencia' => 'down'
                ],
                'tareas' => [
                    'total' => 0,
                    'porcentaje' => 5.0,
                    'tendencia' => 'up'
                ]
            ],
            'usuariosRecientes' => $usuariosRecientes,
            'actividadReciente' => $actividadReciente,
            'tiposCambio' => $tiposCambio,
            'tareasPendientes' => [] // sin modelo Tarea, dejamos vacío
        ]);
    }
    private function getExchangeRates()
    {
        $ufv = \App\Models\ExchangeRate::where('type', 'ufv')
            ->orderBy('date', 'desc')
            ->first();

        $dolarOficial = \App\Models\ExchangeRate::where('type', 'dolar_oficial')
            ->orderBy('date', 'desc')
            ->first();

        $dolarReferencial = \App\Models\ExchangeRate::where('type', 'dolar_referencial')
            ->orderBy('date', 'desc')
            ->first();

        $euro = \App\Models\ExchangeRate::where('type', 'EUR')
            ->orderBy('date', 'desc')
            ->first();

        $real = \App\Models\ExchangeRate::where('type', 'BRL')
            ->orderBy('date', 'desc')
            ->first();

        $fechaActualizacion = collect([$ufv, $dolarOficial, $dolarReferencial])
            ->filter()
            ->max('consulted_at');

        return [
            'ufv' => $ufv ? number_format($ufv->value, 5) : null,
            'dolar_oficial' => $dolarOficial ? [
                'compra' => number_format($dolarOficial->buy_value, 2),
                'venta'  => number_format($dolarOficial->value, 2),
            ] : null,
            'dolar_referencial' => $dolarReferencial ? [
                'compra' => number_format($dolarReferencial->buy_value, 2),
                'venta'  => number_format($dolarReferencial->value, 2),
            ] : null,
            'euro' => $euro ? number_format($euro->value, 4) : null,
            'real' => $real ? number_format($real->value, 4) : null,
            'fecha_actualizacion' => $dolarOficial
            ? Carbon::parse($dolarOficial->fecha_actualizacion)->format('d/m/Y H:i')
            : null,
        ];
    }
}
