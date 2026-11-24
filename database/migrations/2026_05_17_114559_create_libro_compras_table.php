<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libro_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos')->onDelete('cascade');
            $table->foreignId('importacion_id')
                ->nullable()
                ->constrained('importaciones')
                ->nullOnDelete();

            // Datos del SIN (fijos, no editables)
            $table->string('mes', 20)->nullable();
            $table->string('especificacion', 10)->nullable();
            $table->string('nit_proveedor', 20)->nullable();
            $table->string('razon_social', 200)->nullable();
            $table->string('cod_autorizacion', 100)->nullable();
            $table->string('nro_factura', 30)->nullable();
            $table->string('nro_dui_dim', 30)->nullable();
            $table->date('fecha_factura')->nullable();
            $table->decimal('importe_total', 15, 4)->default(0);
            $table->decimal('importe_ice', 15, 4)->default(0);
            $table->decimal('importe_iehd', 15, 4)->default(0);
            $table->decimal('importe_ipj', 15, 4)->default(0);
            $table->decimal('tasas', 15, 4)->default(0);
            $table->decimal('otro_no_sujeto', 15, 4)->default(0);
            $table->decimal('importes_exentos', 15, 4)->default(0);
            $table->decimal('compras_tasa_cero', 15, 4)->default(0);
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('descuento', 15, 4)->default(0);  // DESCUENTOS/BONIFICACIONES
            $table->decimal('importe_gift_card', 15, 4)->default(0);
            $table->decimal('importe_base_cf', 15, 4)->default(0);
            $table->decimal('credito_fiscal', 15, 4)->default(0);   // IVA CF del SIN
            $table->decimal('cf_iva_contable', 15, 4)->default(0);  // IVA CF ajustado
            $table->string('tipo_compra', 60)->nullable();
            $table->string('cod_control', 100)->nullable();
            $table->string('concepto', 100)->nullable();
            $table->decimal('dias_credito', 8, 2)->default(0);

            // Cuentas asignadas por el usuario (editables en pantalla)
            $table->foreignId('cuenta_gasto_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_iva_cf_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_descuento_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_iva_descuento_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_pago_id')->nullable()->constrained('cuentas')->nullOnDelete(); // CAJA o BANCO

            // Montos calculados automáticamente
            $table->decimal('importe_debe', 15, 4)->default(0);   // importe_total - cf_iva_contable
            $table->decimal('importe_haber', 15, 4)->default(0);  // = importe_total

            // Relación con el asiento generado
            $table->foreignId('asiento_id')->nullable()->constrained('asientos')->nullOnDelete();

            // Estado del registro
            $table->enum('estado', ['pendiente', 'contabilizado', 'anulado'])->default('pendiente');

            // Auditoría
            $table->foreignId('importado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('contabilizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('contabilizado_at')->nullable();
            $table->timestamps();

            // Índices para búsquedas frecuentes
            $table->index(['periodo_id', 'estado']);
            $table->index('importacion_id');
            $table->index('fecha_factura');
            $table->index('nit_proveedor');
            $table->index('asiento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libro_compras');
    }
};
