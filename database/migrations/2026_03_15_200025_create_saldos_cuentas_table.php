<?php
// database/migrations/[timestamp]_create_saldos_cuentas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saldos_cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos')->onDelete('cascade');
            $table->foreignId('cuenta_id')->constrained('cuentas')->onDelete('cascade');

            // Saldos por período (seguimos tu estructura de debe/haber)
            $table->decimal('saldo_inicial_debe', 15, 2)->default(0);
            $table->decimal('saldo_inicial_haber', 15, 2)->default(0);

            // Movimientos del período
            $table->decimal('total_debe', 15, 2)->default(0);
            $table->decimal('total_haber', 15, 2)->default(0);

            // Saldos finales
            $table->decimal('saldo_final_debe', 15, 2)->default(0);
            $table->decimal('saldo_final_haber', 15, 2)->default(0);

            // Control
            $table->enum('estado', ['ACTIVO', 'RECALCULADO'])->default('ACTIVO');
            $table->timestamp('ultimo_calculo')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();

            // Una cuenta solo puede tener un registro por período
            $table->unique(['periodo_id', 'cuenta_id']);

            // Índices para búsquedas rápidas
            $table->index(['periodo_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldos_cuentas');
    }
};
