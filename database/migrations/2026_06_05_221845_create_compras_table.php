<?php
// ============================================================
// MIGRACIÓN 9 — compras (cabecera de orden/recepción)
// Una compra puede tener múltiples productos.
// Al confirmar: genera entrada kardex + libro_compras (si tiene factura)
// Escenarios:
//   A) Compra con factura SIN   → libro_compras + kardex + asiento
//   B) Compra sin factura       → solo kardex + asiento (gasto sin CF)
//   C) Compra importación DIM   → igual pero con datos DUI/DIM
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nro_compra', 20)->unique(); // número interno CO-0001

            $table->foreignId('periodo_id')->constrained('periodos');
            $table->foreignId('proveedor_id')
                  ->constrained('proveedores_clientes');
            $table->foreignId('almacen_id')->constrained('almacenes');

            // Datos del documento fiscal (opcional si no hay factura)
            $table->string('nro_factura', 30)->nullable();
            $table->string('cod_autorizacion', 100)->nullable();
            $table->date('fecha_factura')->nullable();
            $table->date('fecha_recepcion');

            // Importes
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('descuento', 15, 4)->default(0);
            $table->decimal('iva_cf', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);

            // Forma de pago
            $table->enum('forma_pago', ['contado', 'credito', 'mixto'])->default('contado');
            $table->integer('dias_credito')->default(0);
            $table->date('fecha_vencimiento_pago')->nullable();

            // Cuentas contables (pueden pre-llenarse desde el proveedor)
            $table->foreignId('cuenta_pago_id')
                  ->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_iva_cf_id')
                  ->nullable()->constrained('cuentas')->nullOnDelete();

            // Relación con libro_compras (si se registra en el SIN)
            $table->foreignId('libro_compra_id')
                  ->nullable()->constrained('libro_compras')->nullOnDelete();

            // Asiento contable generado
            $table->foreignId('asiento_id')
                  ->nullable()->constrained('asientos')->nullOnDelete();

            // Estado del flujo
            $table->enum('estado', [
                'borrador',      // se está editando
                'confirmada',    // stock ingresado, factura pendiente de contabilizar
                'contabilizada', // asiento generado
                'anulada',
            ])->default('borrador');

            $table->text('observacion')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmado_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['periodo_id', 'estado']);
            $table->index('proveedor_id');
            $table->unique(['empresa_id', 'nro_compra']);
        });
    }
    public function down(): void { Schema::dropIfExists('compras'); }
};
