<?php
// ============================================================
// MIGRACIÓN 11 — ventas (cabecera)
// Escenarios cubiertos:
//   A) Venta POS contado + factura SIAT en línea
//   B) Venta POS contado + factura SIAT offline (sin internet)
//   C) Venta POS contado + sin factura (recibo interno)
//   D) Venta a crédito (CxC) + factura SIAT
//   E) Venta anulada después de facturar (anulación en SIAT)
//   F) Nota de débito / crédito sobre venta anterior
// Al confirmar: kardex-- + llamada SIAT + asiento ING
// Contabilización: inmediata o al cierre del día (configurable)
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('nro_venta', 20)->unique(); // VT-0001 (interno)

            $table->foreignId('periodo_id')->constrained('periodos');
            $table->foreignId('almacen_id')->constrained('almacenes');
            $table->foreignId('proveedor_cliente_id') // el cliente
                  ->nullable()->constrained('proveedores_clientes')->nullOnDelete();

            // Datos del cliente para factura (pueden venir del maestro o ingresarse en el POS)
            $table->string('nit_cliente', 20)->nullable();
            $table->string('ci_cliente', 15)->nullable();
            $table->string('complemento_cliente', 5)->nullable();
            $table->string('razon_social_cliente', 200)->nullable();

            $table->date('fecha');
            $table->time('hora')->nullable();

            // Tipo y modalidad
            $table->enum('tipo', [
                'venta',
                'nota_debito',   // cargo adicional sobre venta
                'nota_credito',  // devolución parcial o ajuste
            ])->default('venta');
            $table->unsignedBigInteger('venta_origen_id')->nullable(); // para ND/NC

            // Forma de pago
            $table->enum('forma_pago', ['contado', 'credito', 'mixto', 'tarjeta', 'qr'])->default('contado');
            $table->integer('dias_credito')->default(0);
            $table->date('fecha_vencimiento_cobro')->nullable();

            // Importes
            $table->decimal('subtotal', 15, 4)->default(0);     // suma de líneas s/IVA
            $table->decimal('descuento', 15, 4)->default(0);    // descuento global
            $table->decimal('iva_df', 15, 4)->default(0);       // débito fiscal 13%
            $table->decimal('total', 15, 4)->default(0);        // lo que paga el cliente

            // Cuentas contables
            $table->foreignId('cuenta_cobro_id')                // Caja o CxC
                  ->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_iva_df_id')
                  ->nullable()->constrained('cuentas')->nullOnDelete();

            // ── SIAT Bolivia ──────────────────────────────────────
            $table->boolean('con_factura')->default(true);
            $table->enum('modalidad_factura', [
                'online',       // enviada y aprobada en tiempo real
                'offline',      // emitida sin conexión, sincronizar después
                'sin_factura',  // solo recibo interno
            ])->default('online');

            // Respuesta del SIAT
            $table->string('nro_factura', 30)->nullable();
            $table->string('cod_autorizacion', 100)->nullable();
            $table->string('cod_control', 100)->nullable();
            $table->string('cuf', 200)->nullable();              // Código Único de Factura
            $table->string('cufd', 200)->nullable();             // Código Único Facturación Diaria
            $table->timestamp('fecha_emision_factura')->nullable();
            $table->enum('estado_siat', [
                'pendiente',     // no enviada aún (offline o sin conexión)
                'enviada',       // enviada, esperando respuesta
                'aprobada',      // SIAT confirmó
                'rechazada',     // SIAT rechazó (error técnico o datos)
                'anulada',       // anulada en SIAT
                'sin_factura',   // venta sin factura
            ])->default('pendiente');
            $table->text('respuesta_siat')->nullable();          // JSON de respuesta cruda
            $table->timestamp('enviado_siat_at')->nullable();

            // ── Contabilización ───────────────────────────────────
            $table->foreignId('asiento_id')
                  ->nullable()->constrained('asientos')->nullOnDelete();
            $table->enum('estado_contable', [
                'pendiente',       // no contabilizada
                'contabilizada',   // asiento generado
            ])->default('pendiente');
            $table->timestamp('contabilizado_at')->nullable();
            $table->foreignId('contabilizado_por')
                  ->nullable()->constrained('users')->nullOnDelete();

            // Estado general
            $table->enum('estado', [
                'borrador',
                'confirmada',   // kardex descontado
                'anulada',
            ])->default('borrador');
            $table->string('motivo_anulacion', 200)->nullable();

            $table->text('observacion')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['periodo_id', 'estado', 'estado_contable']);
            $table->index(['estado_siat']);
            $table->index('fecha');
        });
    }
    public function down(): void { Schema::dropIfExists('ventas'); }
};
