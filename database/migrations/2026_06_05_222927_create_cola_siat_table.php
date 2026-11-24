<?php
// ============================================================
// MIGRACIÓN 17 — cola_siat
// Facturas pendientes de envío al SIAT (modo offline).
// Un job de Laravel las procesa cuando hay conexión.
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cola_siat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->onDelete('cascade');

            $table->text('payload_xml');             // XML de la factura a enviar
            $table->string('cuf', 200)->nullable();  // CUF generado offline

            $table->integer('intentos')->default(0);
            $table->timestamp('ultimo_intento_at')->nullable();
            $table->text('ultimo_error')->nullable();

            $table->enum('estado', [
                'pendiente',    // en espera de envío
                'enviando',     // procesando
                'aprobada',     // SIAT confirmó
                'rechazada',    // SIAT rechazó (requiere corrección)
                'anulada',      // se anuló antes de enviar
            ])->default('pendiente');

            $table->timestamps();
            $table->index(['estado', 'intentos']);
        });
    }
    public function down(): void { Schema::dropIfExists('cola_siat'); }
};
