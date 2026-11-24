<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
class PeridosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si los permisos ya existen o crearlos
        $permisosPeriodos = [
            'view periodos',
            'create periodos',
            'edit periodos',
            'delete periodos',
            'cerrar periodos'
        ];

        foreach ($permisosPeriodos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // Asignar todos los permisos al rol SUPERADMIN
        $superAdminRole = Role::findByName('SUPERADMIN');
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($permisosPeriodos);
        } else {
            // Opcional: Crear el rol si no existe
            $superAdminRole = Role::create(['name' => 'SUPERADMIN', 'guard_name' => 'web'])
                ->givePermissionTo($permisosPeriodos);
        }

        // Asignar permisos específicos al rol ADMIN (excluyendo delete)
        $adminRole = Role::findByName('ADMIN');
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'view periodos',
                'create periodos',
                'edit periodos',
                'cerrar periodos'
            ]);
        } else {
            // Opcional: Crear el rol si no existe
            $adminRole = Role::create(['name' => 'ADMIN', 'guard_name' => 'web'])
                ->givePermissionTo([
                    'view periodos',
                    'create periodos',
                    'edit periodos',
                    'cerrar periodos'
                ]);
        }
    }
}
