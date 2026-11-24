<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('libro_ventas', function (Blueprint $table) {
            if (!Schema::hasColumn('libro_ventas', 'venta_id')) {
                $table->foreignId('venta_id')->nullable()->after('periodo_id')
                      ->constrained('ventas')->nullOnDelete();
            }
            if (!Schema::hasColumn('libro_ventas', 'va_a_inventario')) {
                $table->boolean('va_a_inventario')->default(false)->after('estado');
            }
            if (!Schema::hasColumn('libro_ventas', 'almacen_id')) {
                $table->foreignId('almacen_id')->nullable()->after('va_a_inventario')
                      ->constrained('almacenes')->nullOnDelete();
            }
            if (!Schema::hasColumn('libro_ventas', 'estado_inventario')) {
                $table->string('estado_inventario', 20)->nullable()->after('almacen_id');
            }
            if (!Schema::hasColumn('libro_ventas', 'ingresado_inventario_at')) {
                $table->timestamp('ingresado_inventario_at')->nullable()->after('estado_inventario');
            }
            if (!Schema::hasColumn('libro_ventas', 'ingresado_inventario_por')) {
                $table->foreignId('ingresado_inventario_por')->nullable()->after('ingresado_inventario_at')
                      ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('libro_ventas', function (Blueprint $table) {
            foreach (['venta_id', 'va_a_inventario', 'almacen_id', 'estado_inventario',
                      'ingresado_inventario_at', 'ingresado_inventario_por'] as $col) {
                if (Schema::hasColumn('libro_ventas', $col)) {
                    try { $table->dropForeign([$col]); } catch (\Throwable $e) {}
                    $table->dropColumn($col);
                }
            }
        });
    }
};
