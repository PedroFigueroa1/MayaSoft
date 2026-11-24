<?php
// app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use App\Models\DetalleAsiento;
use App\Observers\DetalleAsientoObserver; // Asegúrate de importar el observer
use App\Services\Siat\SiatTransport;
use App\Services\Siat\SiatTransportSoap;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Empresa::class => EmpresaPolicy::class,
        Cuentas::class => CuentasPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SiatTransport::class, SiatTransportSoap::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->shareAuthDataWithInertia();

        // Registrar observers aquí
        $this->registerObservers();
    }

    /**
     * Register model observers
     */
    protected function registerObservers(): void
    {
        // Registrar el observer para DetalleAsiento
        DetalleAsiento::observe(DetalleAsientoObserver::class);

        // Si tienes otros observers, los registras aquí también
        // Ejemplo: Asiento::observe(AsientoObserver::class);
    }

    /**
     * Share authentication data with Inertia.js
     */
    protected function shareAuthDataWithInertia(): void
    {
        Inertia::share([
            'auth' => function () {
                $user = Auth::user();

                if (!$user) {
                    return [
                        'user' => null,
                        'can' => fn ($permission) => false,
                        'hasRole' => fn ($role) => false,
                        'hasAnyRole' => fn ($roles) => false,
                        'roles' => [],
                        'permissions' => [],
                    ];
                }

                $permissions = $this->getCachedUserPermissions($user);
                $roles = $this->getCachedUserRoles($user);

                return [
                    'user' => $user->only('id', 'name', 'email', 'username'),
                    'can' => fn (string $permission): bool => $permissions->contains($permission),
                    'hasRole' => fn (string $role): bool => $roles->contains($role),
                    'hasAnyRole' => fn (array $roleList): bool => $roles->intersect($roleList)->isNotEmpty(),
                    'roles' => $roles,
                    'permissions' => $permissions,
                ];
            },
        ]);
    }

    /**
     * Get user permissions with caching
     */
    protected function getCachedUserPermissions($user): Collection
    {
        return cache()->remember(
            "user.{$user->id}.permissions",
            now()->addHour(),
            fn () => $user->getAllPermissions()->pluck('name')
        );
    }

    /**
     * Get user roles with caching
     */
    protected function getCachedUserRoles($user): Collection
    {
        return cache()->remember(
            "user.{$user->id}.roles",
            now()->addHour(),
            fn () => $user->getRoleNames()
        );
    }
}
