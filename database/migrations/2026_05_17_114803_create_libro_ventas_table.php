<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libro_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos')->onDelete('cascade');
            $table->foreignId('importacion_id')
                ->nullable()
                ->constrained('importaciones')
                ->nullOnDelete();

            // Datos del SIN (fijos)
            $table->string('nro_correlativo', 20)->nullable();
            $table->string('especificacion', 10)->nullable();
            $table->date('fecha_factura')->nullable();
            $table->string('nro_factura', 30)->nullable();
            $table->string('cod_autorizacion', 100)->nullable();
            $table->string('nit_cliente', 20)->nullable();
            $table->string('complemento', 5)->nullable();
            $table->string('razon_social_cliente', 200)->nullable();
            $table->decimal('importe_total', 15, 4)->default(0);
            $table->decimal('importe_ice', 15, 4)->default(0);
            $table->decimal('importe_iehd', 15, 4)->default(0);
            $table->decimal('importe_ipj', 15, 4)->default(0);
            $table->decimal('tasas', 15, 4)->default(0);
            $table->decimal('otros_no_sujetos', 15, 4)->default(0);
            $table->decimal('exportaciones_exentas', 15, 4)->default(0);
            $table->decimal('ventas_tasa_cero', 15, 4)->default(0);
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('descuento', 15, 4)->default(0);  // DESCUENTOS/BONIFICACIONES
            $table->decimal('importe_gift_card', 15, 4)->default(0);
            $table->decimal('importe_base_df', 15, 4)->default(0);
            $table->decimal('debito_fiscal', 15, 4)->default(0);
            $table->enum('estado_factura', ['VALIDA', 'ANULADA'])->default('VALIDA');
            $table->string('cod_control', 100)->nullable();
            $table->string('tipo_venta', 60)->nullable();

            // Cuentas asignadas por el usuario
            $table->foreignId('cuenta_ingreso_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_iva_df_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_descuento_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_iva_descuento_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_cobro_id')->nullable()->constrained('cuentas')->nullOnDelete(); // CAJA o CxC

            // Montos calculados automáticamente
            $table->decimal('importe_debe', 15, 4)->default(0);   // = importe_total (CAJA/CxC)
            $table->decimal('importe_haber', 15, 4)->default(0);  // importe_total - debito_fiscal (ingreso)

            // Relación con el asiento generado
            $table->foreignId('asiento_id')->nullable()->constrained('asientos')->nullOnDelete();

            // Estado del registro
            $table->enum('estado', ['pendiente', 'contabilizado', 'anulado'])->default('pendiente');

            // Auditoría
            $table->foreignId('importado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('contabilizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('contabilizado_at')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['periodo_id', 'estado']);
            $table->index('importacion_id');
            $table->index('fecha_factura');
            $table->index('nit_cliente');
            $table->index('asiento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libro_ventas');
    }
};
