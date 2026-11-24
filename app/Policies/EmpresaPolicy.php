<?php

namespace App\Policies;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmpresaPolicy
{
    use HandlesAuthorization;

    /**
     * Permitir que SUPERADMIN pueda hacer todo sin condiciones adicionales.
     */
    public function before(User $user, $ability): bool|null
    {
        if ($user->hasRole('SUPERADMIN')) {
            return true;
        }

        return null; // Sigue con los métodos individuales si no es superadmin
    }

    /**
     * Determina si el usuario puede ver cualquier modelo Empresa.
     */
    public function viewAny(User $user): bool
    {
        return true; // Ya se permitió a SUPERADMIN en before()
    }

    /**
     * Determina si el usuario puede ver el modelo específico Empresa.
     */
    public function view(User $user, Empresa $empresa): bool
    {
        return $user->id === $empresa->user_id;
    }

    /**
     * Determina si el usuario puede crear modelos Empresa.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determina si el usuario puede actualizar el modelo.
     */
    public function update(User $user, Empresa $empresa): bool
    {
        return $user->id === $empresa->user_id;
    }

    /**
     * Determina si el usuario puede eliminar el modelo.
     */


    /**
     * Determina si el usuario puede restaurar el modelo.
     */
    public function restore(User $user, Empresa $empresa): bool
    {
        return $user->id === $empresa->user_id;
    }

    /**
     * Determina si el usuario puede eliminar permanentemente el modelo.
     */
    public function forceDelete(User $user, Empresa $empresa): bool
    {
        return $user->id === $empresa->user_id;
    }
}
