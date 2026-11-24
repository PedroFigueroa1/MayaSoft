<?php
// ============================================================
// MIGRACIÓN 13 — pagos_ventas
// Una venta puede pagarse con múltiples medios (contado mixto):
// 50% efectivo + 50% QR, etc.
// También registra cobros de ventas a crédito.
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pagos_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->onDelete('cascade');

            $table->enum('medio', [
                'efectivo',
                'tarjeta_debito',
                'tarjeta_credito',
                'qr',           // QR Bolivia (Tigo Money, etc.)
                'transferencia',
                'cheque',
                'credito',      // queda pendiente en CxC
            ]);

            $table->decimal('monto', 15, 4);
            $table->decimal('cambio', 15, 4)->default(0);  // vuelto en efectivo
            $table->date('fecha');
            $table->string('referencia', 100)->nullable();  // nro transacción, voucher

            // Cuenta contable donde entra el dinero
            $table->foreignId('cuenta_id')
                  ->nullable()->constrained('cuentas')->nullOnDelete();

            $table->timestamps();
            $table->index('venta_id');
        });
    }
    public function down(): void { Schema::dropIfExists('pagos_ventas'); }
};
