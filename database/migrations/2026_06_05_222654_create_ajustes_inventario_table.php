<?php
// ============================================================
// MIGRACIÓN 14 — ajustes_inventario
// Cabecera de un recuento físico.
// Puede ajustar muchos productos en una sola sesión.
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ajustes_inventario', function (Blueprint $table) {
            $table->id();
            $table->string('nro_ajuste', 20)->unique();    // AJ-0001
            $table->foreignId('periodo_id')->constrained('periodos');
            $table->foreignId('almacen_id')->constrained('almacenes');
            $table->date('fecha_recuento');

            $table->enum('tipo', [
                'recuento_fisico',   // inventario físico periódico
                'ajuste_precio',     // corrección de costo sin mover cantidad
                'merma',             // pérdida masiva (ej: siniestro)
                'donacion',          // salida sin venta
            ])->default('recuento_fisico');

            $table->text('observacion')->nullable();
            $table->foreignId('asiento_id')
                  ->nullable()->constrained('asientos')->nullOnDelete();

            $table->enum('estado', ['borrador', 'confirmado', 'contabilizado'])->default('borrador');
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmado_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ajustes_inventario'); }
};
