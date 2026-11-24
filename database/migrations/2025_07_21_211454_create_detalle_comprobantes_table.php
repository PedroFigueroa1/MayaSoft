<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('detalle_comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobante_id')->constrained('comprobantes')->onDelete('cascade');
            $table->string('producto_servicio', 255);
            $table->decimal('cantidad', 10, 2);
            $table->enum('unidad_medida', [
                'UN', 'KG', 'M', 'L', 'GL', 'OT'
            ])->default('UN');
            $table->decimal('precio_unitario', 15, 2);
            $table->decimal('tasa_iva', 5, 2)->default(13);
            $table->decimal('tasa_it', 5, 2)->default(3);
            $table->decimal('tasa_ice', 5, 2)->default(0);
            $table->decimal('descuento', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('importe_iva', 15, 2);
            $table->decimal('importe_it', 15, 2);
            $table->decimal('importe_ice', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_comprobantes');
    }
};
