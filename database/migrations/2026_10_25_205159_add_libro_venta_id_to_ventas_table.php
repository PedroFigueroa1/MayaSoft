<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            if (!Schema::hasColumn('ventas', 'libro_venta_id')) {
                $table->foreignId('libro_venta_id')
                      ->nullable()
                      ->after('periodo_id')
                      ->constrained('libro_ventas')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            if (Schema::hasColumn('ventas', 'libro_venta_id')) {
                try { $table->dropForeign(['libro_venta_id']); } catch (\Throwable $e) {}
                $table->dropColumn('libro_venta_id');
            }
        });
    }
};
