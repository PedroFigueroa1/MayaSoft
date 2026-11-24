
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('rol', 30)->default('operador');

            $table->timestamps();

            $table->unique(['empresa_id', 'user_id']);
        });

        // Backfill 1: usuarios que tienen empresa_id
        if (Schema::hasColumn('users', 'empresa_id')) {

            DB::table('users')
                ->whereNotNull('empresa_id')
                ->orderBy('id')
                ->chunkById(500, function ($usuarios) {

                    foreach ($usuarios as $u) {

                        DB::table('empresa_user')->insertOrIgnore([
                            'empresa_id' => $u->empresa_id,
                            'user_id' => $u->id,
                            'rol' => 'operador',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
        }

        // Backfill 2: propietarios de empresas
        if (Schema::hasColumn('empresas', 'user_id')) {

            DB::table('empresas')
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->chunkById(500, function ($empresas) {

                    foreach ($empresas as $e) {

                        DB::table('empresa_user')->updateOrInsert(
                            [
                                'empresa_id' => $e->id,
                                'user_id' => $e->user_id,
                            ],
                            [
                                'rol' => 'admin',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_user');
    }
};
