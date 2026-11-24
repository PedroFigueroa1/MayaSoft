<?php
// ============================================================
// MIGRACIÓN 6 — stock_actual
// Saldo desnormalizado por producto×almacén.
// Se actualiza con cada movimiento (dentro de DB::transaction).
// Permite consultas instantáneas de disponibilidad en el POS.
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_actual', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('cascade');

            $table->decimal('cantidad', 15, 4)->default(0);
            $table->decimal('costo_promedio', 15, 4)->default(0); // para método PROM
            $table->decimal('costo_total', 15, 4)->default(0);    // cantidad × costo_promedio

            $table->timestamp('ultimo_movimiento')->nullable();

            $table->timestamps();

            // Un solo registro por combinación producto+almacén
            $table->unique(['producto_id', 'almacen_id']);
            $table->index('producto_id');
            $table->index('almacen_id');
        });
    }
    public function down(): void { Schema::dropIfExists('stock_actual'); }
};
