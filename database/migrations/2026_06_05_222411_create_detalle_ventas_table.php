<?php
// ============================================================
// MIGRACIÓN 12 — detalle_ventas
// Líneas de una venta. Cada línea descuenta su propio kardex.
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')
                  ->constrained('ventas')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos');

            $table->decimal('cantidad', 15, 4);
            $table->decimal('precio_unitario', 15, 4);
            $table->decimal('descuento', 15, 4)->default(0);
            $table->decimal('subtotal', 15, 4);             // cantidad × precio - descuento
            $table->decimal('iva', 15, 4)->default(0);
            $table->decimal('total', 15, 4);

            // Costo al momento de la venta (capturado del kardex)
            $table->decimal('costo_unitario', 15, 4)->default(0);
            $table->decimal('costo_total', 15, 4)->default(0);
            $table->decimal('utilidad', 15, 4)->default(0); // total - costo_total

            // Referencia al movimiento kardex generado
            $table->foreignId('movimiento_id')
                  ->nullable()->constrained('movimientos_inventario')->nullOnDelete();

            // Para PEPS: el lote específico que se consumió
            $table->foreignId('lote_id')
                  ->nullable()->constrained('lotes_peps')->nullOnDelete();

            $table->timestamps();
            $table->index('venta_id');
            $table->index('producto_id');
        });
    }
    public function down(): void { Schema::dropIfExists('detalle_ventas'); }
};
