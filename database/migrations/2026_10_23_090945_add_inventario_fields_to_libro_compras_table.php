<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('libro_compras', function (Blueprint $table) {
            // Se agregan todas las columnas nuevas
            $table->boolean('va_a_inventario')
                ->default(false)
                ->after('estado'); // opcional: ubicación visual

            $table->foreignId('almacen_id')
                ->nullable()
                ->after('va_a_inventario')
                ->constrained('almacenes')
                ->nullOnDelete(); // recomendado: evita romper si se borra un almacén

            $table->enum('estado_inventario', ['no_aplica', 'pendiente', 'ingresado'])
                ->default('no_aplica')
                ->after('almacen_id');

            $table->timestamp('ingresado_inventario_at')
                ->nullable()
                ->after('estado_inventario');

            $table->foreignId('ingresado_inventario_por')
                ->nullable()
                ->after('ingresado_inventario_at')
                ->constrained('users')
                ->nullOnDelete(); // faltaba la FK en tu propuesta

            // Índice útil para consultas de pendientes de inventario
            $table->index(['estado_inventario', 'periodo_id'], 'libro_compras_estado_inv_periodo_idx');
        });
    }

    public function down(): void
    {
        Schema::table('libro_compras', function (Blueprint $table) {
            // Primero se eliminan las FKs y luego las columnas
            $table->dropForeign(['almacen_id']);
            $table->dropForeign(['ingresado_inventario_por']);
            $table->dropIndex('libro_compras_estado_inv_periodo_idx');

            $table->dropColumn([
                'va_a_inventario',
                'almacen_id',
                'estado_inventario',
                'ingresado_inventario_at',
                'ingresado_inventario_por',
            ]);
        });
    }
};
