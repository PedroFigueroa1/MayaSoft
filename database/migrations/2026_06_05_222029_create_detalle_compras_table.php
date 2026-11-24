<?php
// ============================================================
// MIGRACIÓN 10 — detalle_compras
// Líneas de una orden de compra.
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detalle_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')
                  ->constrained('compras')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos');

            $table->decimal('cantidad', 15, 4);
            $table->decimal('costo_unitario', 15, 4);      // precio acordado con proveedor
            $table->decimal('descuento', 15, 4)->default(0);
            $table->decimal('subtotal', 15, 4);             // cantidad × costo - descuento

            // Referencia al movimiento de inventario generado
            $table->foreignId('movimiento_id')
                  ->nullable()->constrained('movimientos_inventario')->nullOnDelete();

            $table->timestamps();
            $table->index('compra_id');
        });
    }
    public function down(): void { Schema::dropIfExists('detalle_compras'); }
};
