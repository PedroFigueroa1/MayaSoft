<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Creación de Permisos (Agrupados por módulo)
        $this->createPermissions();

        // 2. Asignación de Permisos a Roles
        $this->assignPermissionsToRoles();

        // 3. Verificar y asignar SUPERADMIN
        $this->assignSuperAdminRole();
    }

    protected function createPermissions(): void
    {
        $modules = [
            'dashboard' => ['ver'],
            'usuarios' => ['listar', 'crear', 'editar', 'eliminar', 'activar'],
            'roles' => ['listar', 'crear', 'editar', 'eliminar', 'asignar'],
            'permisos' => ['listar', 'asignar'],
            'facturas' => ['listar', 'crear', 'editar', 'eliminar', 'aprobar', 'anular', 'exportar'],
            'clientes' => ['listar', 'crear', 'editar', 'eliminar', 'exportar'],
            'reportes' => ['generar', 'exportar'],
            'auditoria' => ['listar', 'detalles'],
            'configuracion' => ['general', 'backup', 'restore'],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissionName = "{$action}_{$module}";
                Permission::firstOrCreate(['name' => $permissionName]);
            }
        }

        // Permisos especiales
        Permission::firstOrCreate(['name' => 'acceso_total']);
        Permission::firstOrCreate(['name' => 'panel_administracion']);
    }

    protected function assignPermissionsToRoles(): void
    {
        // SUPERADMIN - Todos los permisos
        $superAdmin = Role::findByName('SUPERADMIN');
        $superAdmin->syncPermissions(Permission::all());

        // ADMIN - Permisos administrativos (sin acceso total)
        $admin = Role::findByName('ADMIN');
        $admin->syncPermissions([
            'ver_dashboard',
            'listar_usuarios', 'crear_usuarios', 'editar_usuarios', 'activar_usuarios',
            'listar_roles', 'asignar_roles',
            'listar_permisos',
            'listar_facturas', 'crear_facturas', 'editar_facturas', 'aprobar_facturas', 'exportar_facturas',
            'listar_clientes', 'crear_clientes', 'editar_clientes', 'exportar_clientes',
            'generar_reportes', 'exportar_reportes',
            'panel_administracion'
        ]);

        // CONTADOR - Permisos financieros
        $contador = Role::findByName('CONTADOR');
        $contador->syncPermissions([
            'ver_dashboard',
            'listar_facturas', 'crear_facturas', 'editar_facturas', 'aprobar_facturas', 'exportar_facturas',
            'listar_clientes', 'exportar_clientes',
            'generar_reportes', 'exportar_reportes'
        ]);

        // AUDITOR - Permisos de revisión
        $auditor = Role::findByName('AUDITOR');
        $auditor->syncPermissions([
            'ver_dashboard',
            'listar_facturas', 'exportar_facturas',
            'listar_clientes', 'exportar_clientes',
            'listar_auditoria', 'detalles_auditoria',
            'generar_reportes', 'exportar_reportes'
        ]);

        // USUARIO - Permisos básicos
        $usuario = Role::findByName('USUARIO');
        $usuario->syncPermissions([
            'ver_dashboard',
            'listar_facturas',
            'listar_clientes'
        ]);
    }

    protected function assignSuperAdminRole(): void
    {
        // Asignar SUPERADMIN al primer usuario o a un email específico
        $user = User::first();
        // O: $user = User::where('email', 'superadmin@example.com')->first();

        if ($user && !$user->hasRole('SUPERADMIN')) {
            $user->assignRole('SUPERADMIN');

            // Opcional: Asignar también el permiso 'acceso_total' directamente
            $user->givePermissionTo('acceso_total');
        }
    }
}
