<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;


class UserController extends Controller
{
    public function index(Request $request)
    {
        // $this->authorize('viewAny', User::class);

        $search = $request->input('search', '');

        $users = User::with(['roles', 'permissions'])
            ->withCount('empresas')  // Cuenta cuántas empresas tiene cada usuario
            ->with(['empresas' => function ($query) {
                // Trae solo los datos necesarios de las empresas (ej: nombre, id)
                $query->select('id', 'nombre', 'user_id');
            }])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                    // Puedes agregar más campos si lo deseas
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString(); // Mantiene el parámetro search en la paginación

        $roles = Role::all();
        $permissions = Permission::all()->groupBy('group');

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'groupedPermissions' => $permissions,
            'filters' => ['search' => $search], // Para mantener el valor en el frontend
        ]);
    }

    public function create()
    {
        // $this->authorize('create', User::class);

        return Inertia::render('Admin/Users/Create', [
            'roles' => Role::all(),
        ]);
    }

    public function store(Request $request)
    {
        // $this->authorize('create', User::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles' => 'sometimes|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'active' => true,
        ]);

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }


    public function show(User $user)
    {
        // $this->authorize('view', $user);

        $user->load(['roles', 'permissions']);

        // Asegurar que siempre haya un array de roles
        $userRoles = $user->roles->pluck('name')->toArray();

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'roles' => Role::all(),
            'permissions' => Permission::all()->groupBy('group'),
            'userRoles' => $userRoles,
            'userPermissions' => $user->getDirectPermissions()->pluck('name')->toArray(),
        ]);
    }

    public function edit(User $user)
    {
        // $this->authorize('update', $user);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'roles' => Role::all(),
            'userRoles' => $user->roles->pluck('name')->toArray(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $isSuperAdmin = auth()->user()->hasRole('SUPERADMIN');

        $rules = [
            'name'  => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ];

        // Solo SUPERADMIN puede cambiar contraseña de otros usuarios
        if ($isSuperAdmin) {
            $rules['password'] = ['nullable', 'confirmed', Rules\Password::defaults()];
        }

        $request->validate($rules);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        // Solo SUPERADMIN puede cambiar contraseña
        if ($isSuperAdmin && $request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);

            Log::info("Contraseña cambiada por SUPERADMIN", [
                'target_user' => $user->id,
                'by'          => auth()->id(),
                'ip'          => $request->ip(),
            ]);
        }

        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }
    public function resetPassword(Request $request, User $user)
    {
        if (!auth()->user()->hasRole('SUPERADMIN')) {
            abort(403, 'Solo el SUPERADMIN puede resetear contraseñas.');
        }

        // Generar contraseña temporal segura
        $tempPassword = Str::password(12); // Laravel 9+, genera algo como "Ab3#kL9!pQ2x"

        $user->update([
            'password'                  => Hash::make($tempPassword),
            'must_change_password'      => true, // campo opcional para forzar cambio
        ]);

        Log::warning("Contraseña reseteada por SUPERADMIN", [
            'target_user' => $user->id,
            'by'          => auth()->id(),
            'ip'          => $request->ip(),
        ]);

        return back()->with([
            'temp_password' => $tempPassword, // Se muestra UNA sola vez
            'success'       => 'Contraseña reseteada. Comparte esta contraseña temporal con el usuario.',
        ]);
    }

    public function destroy(User $user)
    {
        // $this->authorize('delete', $user);

        // Prevenir que un SUPERADMIN se elimine a sí mismo
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
    }

    public function toggleStatus(Request $request, User $user)
    {
        // Autorización: solo admin puede hacer esto (personaliza según tu lógica)
        //if (!Gate::allows('admin-only')) {
        //    abort(403, 'No tienes permiso para cambiar estados.');
        //}

        // Evitar que el usuario se desactive a sí mismo
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'No puedes cambiar tu propio estado'], 403);
        }

        // Cambiar el estado
        $user->active = !$user->active;
        $user->save();

        // Retornar respuesta adecuada para Inertia (puede ser redirect o JSON)
        return redirect()->back()->with('success', 'Estado actualizado correctamente');
        // O si prefieres respuesta JSON para fetch:
        // return response()->json(['message' => 'Estado actualizado', 'active' => $user->active]);
    }

    public function assignRoles(Request $request, User $user)
    {
        // Descomentar cuando la política esté implementada
        // $this->authorize('assignRoles', $user);

        $validated = $request->validate([
            'roles' => 'array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        try {
            DB::beginTransaction();

            // Convertir a array vacío si no se recibe nada
            $roles = $validated['roles'] ?? [];

            // Verificar si el usuario es SUPERADMIN y está intentando quitarse el rol
            if ($user->id === auth()->id() && !in_array('SUPERADMIN', $roles) && $user->hasRole('SUPERADMIN')) {
                DB::rollBack();
                return back()->with('error', 'No puedes quitarte el rol SUPERADMIN a ti mismo.');
            }

            // syncRoles con el array (vacío si no hay roles seleccionados)
            $user->syncRoles($roles);

            Log::info("Roles actualizados para usuario {$user->id}", [
                'by' => auth()->id(),
                'new_roles' => $roles
            ]);

            DB::commit();

            return back()->with('success', 'Roles actualizados correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Error al actualizar roles: " . $e->getMessage(), [
                'user_id' => $user->id,
                'input' => $request->all()
            ]);

            return back()->with('error', 'Ocurrió un error al actualizar los roles.');
        }
    }

    public function assignPermissions(Request $request, User $user)
    {
        // $this->authorize('assignPermissions', $user);

        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $user->syncPermissions($request->permissions ?? []);

        return back()->with('success', 'Permisos actualizados correctamente.');
    }
}
