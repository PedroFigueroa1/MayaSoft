<?php

namespace Database\Seeders;


use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = ['SUPERADMIN', 'ADMIN', 'CONTADOR', 'AUDITOR', 'USUARIO'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName],
                ['guard_name' => 'web'] // especificar el guard
            );
        }

        // Crear usuario y asignarle rol
        $user = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('SuperPass123'),
        ]);

        $user->assignRole('SUPERADMIN'); // ahora sí funcionará
        $this->call([
            RolesSeeder::class,
            EmpresaSeeder::class,
            PeridosSeeder::class,
        ]);
    }
}
