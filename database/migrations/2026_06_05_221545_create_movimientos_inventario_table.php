<?php
// ============================================================
// MIGRACIÓN 8 — movimientos_inventario (KARDEX)
// Registro histórico INMUTABLE de cada movimiento.
// NUNCA se elimina un registro, solo se anulan.
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();

            // Tipo de movimiento
            $table->enum('tipo', [
                // ENTRADAS (suman stock)
                'entrada_compra',       // recepción de mercadería de proveedor
                'entrada_devolucion',   // cliente devuelve producto
                'entrada_produccion',   // producto terminado de fabricación
                'entrada_manual',       // saldo inicial, donación, etc.
                'entrada_traspaso',     // recepción de otro almacén
                // SALIDAS (restan stock)
                'salida_venta',         // venta con factura
                'salida_consumo',       // uso interno sin factura
                'salida_merma',         // pérdida, daño, vencimiento
                'salida_devolucion',    // devuelve al proveedor
                'salida_produccion',    // materia prima usada en fabricación
                'salida_traspaso',      // envío a otro almacén
                // AJUSTES
                'ajuste_positivo',      // sobrante en recuento físico
                'ajuste_negativo',      // faltante en recuento físico
            ]);

            // Qué producto y dónde
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('almacen_id')->constrained('almacenes');
            $table->foreignId('almacen_destino_id')   // para traspasos
                  ->nullable()->constrained('almacenes')->nullOnDelete();

            // Cantidades y costos
            $table->decimal('cantidad', 15, 4);
            $table->decimal('costo_unitario', 15, 4)->default(0);
            $table->decimal('costo_total', 15, 4)->default(0);     // cantidad × costo_unitario
            $table->decimal('precio_venta', 15, 4)->default(0);    // solo en salida_venta

            // Snapshot del stock después del movimiento (auditoría)
            $table->decimal('stock_resultante', 15, 4)->default(0);

            // Para PEPS: referencia al lote consumido/creado
            $table->foreignId('lote_id')
                  ->nullable()->constrained('lotes_peps')->nullOnDelete();

            // Referencia polimórfica al origen del movimiento
            // Puede apuntar a: libro_compras, ventas, compras, ajustes_inventario, etc.
            $table->string('referencia_tipo', 60)->nullable(); // nombre del modelo
            $table->unsignedBigInteger('referencia_id')->nullable();

            // Asiento contable generado
            $table->foreignId('asiento_id')
                  ->nullable()->constrained('asientos')->nullOnDelete();

            // Periodo contable
            $table->foreignId('periodo_id')->constrained('periodos');

            // Datos adicionales
            $table->date('fecha');
            $table->string('motivo', 200)->nullable();
            $table->text('observacion')->nullable();
            $table->foreignId('proveedor_cliente_id')
                  ->nullable()->constrained('proveedores_clientes')->nullOnDelete();

            // Estado
            $table->enum('estado', ['activo', 'anulado'])->default('activo');
            $table->string('motivo_anulacion', 200)->nullable();
            $table->foreignId('anulado_por')
                  ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('anulado_at')->nullable();

            // Auditoría
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Índices para rendimiento
            $table->index(['producto_id', 'almacen_id', 'fecha']);
            $table->index(['referencia_tipo', 'referencia_id']);
            $table->index(['periodo_id', 'tipo']);
            $table->index('fecha');
        });
    }
    public function down(): void { Schema::dropIfExists('movimientos_inventario'); }
};
