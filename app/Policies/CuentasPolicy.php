<?php

namespace App\Policies;

use App\Models\Cuentas;
use App\Models\User;
use Illuminate\Auth\Access\Response;

use Illuminate\Auth\Access\HandlesAuthorization;

class CuentasPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->can('view cuentas');
    }

    public function view(User $user, Cuentas $cuentas)
    {
        return $user->can('view cuentas');
    }

    public function create(User $user)
    {
        return $user->can('create cuentas');
    }

    public function update(User $user)
    {
        return $user->can('edit cuentas'); // o cualquier lógica que uses
    }

    public function delete(User $user, Cuentas $cuentas)
    {
        return $user->can('delete cuentas') && !$cuentas->subcuentas()->exists();
    }
}
