<?php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Empresa;
use App\Policies\EmpresaPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Empresa::class => EmpresaPolicy::class,
        // otras políticas aquí...
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Puedes definir Gates aquí si quieres
        // Gate::define('crear-empresa', fn ($user) => $user->hasPermission('crear_empresa'));
    }
}
