<?php
// ============================================================
// MIGRACIÓN 19 — traspasos_almacen
// Mover stock entre depósitos.
// Genera 2 movimientos kardex: salida_traspaso + entrada_traspaso
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('traspasos_almacen', function (Blueprint $table) {
            $table->id();
            $table->string('nro_traspaso', 20)->unique();  // TR-0001
            $table->foreignId('periodo_id')->constrained('periodos');
            $table->foreignId('almacen_origen_id')->constrained('almacenes');
            $table->foreignId('almacen_destino_id')->constrained('almacenes');
            $table->date('fecha');
            $table->text('motivo')->nullable();
            $table->foreignId('asiento_id')
                  ->nullable()->constrained('asientos')->nullOnDelete();
            $table->enum('estado', ['borrador','confirmado','anulado'])->default('borrador');
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('detalle_traspasos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('traspaso_id')
                  ->constrained('traspasos_almacen')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos');
            $table->decimal('cantidad', 15, 4);
            $table->decimal('costo_unitario', 15, 4)->default(0);
            $table->foreignId('movimiento_salida_id')
                  ->nullable()->constrained('movimientos_inventario')->nullOnDelete();
            $table->foreignId('movimiento_entrada_id')
                  ->nullable()->constrained('movimientos_inventario')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('detalle_traspasos');
        Schema::dropIfExists('traspasos_almacen');
    }
};
