<?php
// ============================================================
// MIGRACIÓN 7 — lotes_peps
// Solo para productos con método PEPS.
// Cada entrada crea un lote con su costo y cantidad disponible.
// Al vender, se consumen los lotes más antiguos primero.
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lotes_peps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('cascade');

            $table->decimal('cantidad_inicial', 15, 4);
            $table->decimal('cantidad_disponible', 15, 4); // va disminuyendo
            $table->decimal('costo_unitario', 15, 4);

            $table->date('fecha_entrada');
            $table->date('fecha_vencimiento')->nullable(); // para perecederos

            // Referencia al movimiento que creó este lote
            $table->unsignedBigInteger('movimiento_origen_id')->nullable();

            $table->boolean('agotado')->default(false)->index();
            $table->timestamps();

            $table->index(['producto_id', 'almacen_id', 'agotado', 'fecha_entrada']);
        });
    }
    public function down(): void { Schema::dropIfExists('lotes_peps'); }
};
