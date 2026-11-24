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
// database/migrations/xxxx_create_movimientos_cuenta_corriente_table.php
        Schema::create('movimientos_cuenta_corriente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('proveedor_cliente_id')->constrained('proveedores_clientes')->cascadeOnDelete();
            $table->date('fecha');
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('tipo', ['cargo', 'abono']); // cargo = deuda, abono = pago
            $table->string('concepto'); // "Factura 001", "Pago", "Nota crédito"
            $table->string('documento_tipo', 30)->nullable(); // factura, recibo, nota
            $table->string('documento_numero', 50)->nullable();
            $table->decimal('monto', 14, 2);
            $table->decimal('saldo', 14, 2)->default(0); // saldo acumulado
            $table->string('moneda', 3)->default('BOB');
            $table->enum('estado', ['pendiente', 'pagado', 'parcial', 'anulado'])->default('pendiente');
            $table->foreignId('referencia_id')->nullable(); // id de venta/compra
            $table->string('referencia_tipo', 50)->nullable(); // Venta, Compra
            $table->foreignId('creado_por')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['proveedor_cliente_id', 'fecha']);
            $table->index(['empresa_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_cuenta_corriente');
    }
};
