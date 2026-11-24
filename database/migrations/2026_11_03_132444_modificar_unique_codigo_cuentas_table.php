
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {

            // Eliminar índice único anterior
            $table->dropUnique('cuentas_codigo_user_unique');

            // Crear índice único por usuario y código
            $table->unique(
                ['user_id', 'codigo'],
                'cuentas_user_codigo_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {

            $table->dropUnique('cuentas_user_codigo_unique');

            $table->unique(
                ['codigo', 'user_id'],
                'cuentas_codigo_user_unique'
            );
        });
    }
};
