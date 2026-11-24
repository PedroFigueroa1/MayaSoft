<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EmpresaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
                // Permisos para Empresa
        $permissions = [
            'empresa.viewAny',
            'empresa.view',
            'empresa.create',
            'empresa.update',
            'empresa.delete',
            'empresa.restore',
            'empresa.forceDelete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Asignar todos los permisos al rol SUPERADMIN
        $role = Role::firstOrCreate(['name' => 'SUPERADMIN']);
        $role->givePermissionTo($permissions);
    }
}
