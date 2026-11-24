<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Periodo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PeriodoPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        // SUPERADMIN tiene todos los permisos
        if ($user->hasRole('SUPERADMIN')) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        return $user->can('view periodos');
    }

    public function view(User $user, Periodo $periodo)
    {
        return $user->can('view periodos') &&
               ($this->perteneceAEmpresa($user, $periodo) ||
               $user->hasRole('ADMIN'));
    }

    public function create(User $user)
    {
        return $user->can('create periodos');
    }

    public function update(User $user, Periodo $periodo)
    {
        return $user->can('edit periodos') &&
               $periodo->estaAbierto() &&
               ($this->perteneceAEmpresa($user, $periodo) ||
               $user->hasRole('ADMIN'));
    }

    public function delete(User $user, Periodo $periodo)
    {
        return $user->can('delete periodos') &&
               $periodo->estaAbierto() &&
               !$periodo->asientos()->exists() &&
               ($this->perteneceAEmpresa($user, $periodo) ||
               $user->hasRole('ADMIN'));
    }

    //public function cerrar(User $user, Periodo $periodo)
    //{
    //    return $user->can('cerrar periodos') &&
    //           $periodo->estaAbierto() &&
    //           ($this->perteneceAEmpresa($user, $periodo) ||
    //           $user->hasRole('ADMIN'));
    //}

    /**
     * Verifica si el usuario pertenece a la empresa del período
     */
    protected function perteneceAEmpresa(User $user, Periodo $periodo): bool
    {
        return $user->empresas->contains($periodo->empresa_id);
    }
}
