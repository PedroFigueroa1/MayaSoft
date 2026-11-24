<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Permission::class);

        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => Permission::latest()->paginate(10),
            'groups' => Permission::select('group')->distinct()->pluck('group'),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Permission::class);

        return Inertia::render('Admin/Permissions/Create', [
            'groups' => Permission::select('group')->distinct()->pluck('group'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Permission::class);

        $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'group' => 'required|string',
            'description' => 'nullable|string',
        ]);

        Permission::create([
            'name' => $request->name,
            'group' => $request->group,
            'description' => $request->description,
            'guard_name' => 'web',
        ]);

        return redirect()->route('permissions.index')->with('success', 'Permiso creado correctamente.');
    }

    public function edit(Permission $permission)
    {
        $this->authorize('update', $permission);

        return Inertia::render('Admin/Permissions/Edit', [
            'permission' => $permission,
            'groups' => Permission::select('group')->distinct()->pluck('group'),
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $this->authorize('update', $permission);

        $request->validate([
            'name' => 'required|string|unique:permissions,name,'.$permission->id,
            'group' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $permission->update([
            'name' => $request->name,
            'group' => $request->group,
            'description' => $request->description,
        ]);

        return redirect()->route('permissions.index')->with('success', 'Permiso actualizado correctamente.');
    }

    public function destroy(Permission $permission)
    {
        $this->authorize('delete', $permission);

        $permission->delete();

        return redirect()->route('permissions.index')->with('success', 'Permiso eliminado correctamente.');
    }
}
