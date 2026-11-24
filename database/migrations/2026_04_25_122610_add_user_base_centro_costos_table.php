<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centro_costos', function (Blueprint $table) {
            $table->foreignId('user_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->boolean('es_base')
                  ->default(false)
                  ->after('user_id');

            // Unique compuesto: mismo código puede existir para distintos usuarios
            $table->dropUnique(['codigo']);
            $table->unique(['codigo', 'user_id'], 'centro_costos_codigo_user_unique');
        });

        // Marcar los registros existentes como base
        DB::table('centro_costos')->update([
            'es_base' => true,
            'user_id' => null,
        ]);
    }

    public function down(): void
    {
        Schema::table('centro_costos', function (Blueprint $table) {
            $table->dropUnique('centro_costos_codigo_user_unique');
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'es_base']);
            $table->unique(['codigo']);
        });
    }
};
