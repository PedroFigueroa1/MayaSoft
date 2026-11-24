<?php
// ============================================================
// MIGRACIÓN 18 — cierres_caja
// Cierre diario del POS.
// Al cerrar: contabiliza todas las ventas del día en un
// solo asiento (o uno por venta, configurable).
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cierres_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almacen_id')->constrained('almacenes');
            $table->foreignId('periodo_id')->constrained('periodos');
            $table->date('fecha');

            // Resumen del día
            $table->integer('total_ventas')->default(0);       // cantidad de ventas
            $table->integer('total_facturas')->default(0);     // facturas emitidas
            $table->decimal('total_efectivo', 15, 4)->default(0);
            $table->decimal('total_tarjeta', 15, 4)->default(0);
            $table->decimal('total_qr', 15, 4)->default(0);
            $table->decimal('total_credito', 15, 4)->default(0);
            $table->decimal('total_general', 15, 4)->default(0);
            $table->decimal('total_iva_df', 15, 4)->default(0);

            // Modo de contabilización
            $table->enum('modo_contabilizacion', [
                'por_venta',    // un asiento por cada venta
                'diario',       // un solo asiento consolidado del día
            ])->default('diario');

            // Asiento consolidado (para modo 'diario')
            $table->foreignId('asiento_id')
                  ->nullable()->constrained('asientos')->nullOnDelete();

            $table->enum('estado', ['abierto', 'cerrado', 'contabilizado'])->default('abierto');
            $table->foreignId('cerrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cerrado_at')->nullable();
            $table->foreignId('contabilizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('contabilizado_at')->nullable();
            $table->timestamps();

            $table->unique(['almacen_id', 'fecha']); // un cierre por almacén por día
            $table->index('fecha');
        });
    }
    public function down(): void { Schema::dropIfExists('cierres_caja'); }
};
