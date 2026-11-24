<?php
// ============================================================
// MIGRACIÓN 4 — productos
// Catálogo maestro. Cada producto tiene su método de costeo
// y sus 3 cuentas contables para automatizar asientos.
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();

            // Identificación
            $table->string('codigo', 30)->unique();
            $table->string('codigo_barra', 50)->nullable()->index();
            $table->string('descripcion', 200);
            $table->text('descripcion_larga')->nullable();

            // Clasificación
            $table->foreignId('categoria_id')
                  ->nullable()->constrained('categorias_productos')->nullOnDelete();
            $table->foreignId('unidad_medida_id')
                  ->constrained('unidades_medida');

            // Tipo de producto
            $table->enum('tipo', [
                'mercaderia',     // compra y vende tal cual
                'materia_prima',  // insumo para producción
                'producto_terminado', // resultado de producción
                'servicio',       // no mueve inventario físico
                'activo_fijo',    // no pasa por kardex normal
            ])->default('mercaderia');

            // Precios
            $table->decimal('precio_costo', 15, 4)->default(0);
            $table->decimal('precio_venta', 15, 4)->default(0);
            $table->decimal('precio_venta_2', 15, 4)->default(0);  // precio mayorista
            $table->decimal('precio_venta_3', 15, 4)->default(0);  // precio especial

            // IVA (Bolivia: 13%)
            $table->boolean('aplica_iva')->default(true);
            $table->decimal('porcentaje_iva', 5, 2)->default(13.00);

            // Método de costeo (por producto, Bolivia acepta los 3)
            $table->enum('metodo_costeo', ['PROM', 'PEPS', 'ESP'])->default('PROM');
            // PROM = Promedio ponderado (más común en Bolivia)
            // PEPS = Primero en entrar, primero en salir
            // ESP  = Precio específico (para bienes identificables)

            // Cuentas contables (se usan para generar asientos automáticos)
            $table->foreignId('cuenta_activo_id')       // Almacén / Mercaderías
                  ->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_costo_venta_id')  // Costo de mercaderías vendidas
                  ->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_ingreso_id')      // Ventas / Ingresos
                  ->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_variacion_id')    // Variación de inventario (ajustes)
                  ->nullable()->constrained('cuentas')->nullOnDelete();

            // Control de stock
            $table->decimal('stock_minimo', 15, 4)->default(0);
            $table->decimal('stock_maximo', 15, 4)->default(0);
            $table->boolean('controla_stock')->default(true);  // false para servicios

            // Para SIAT Bolivia: código de actividad económica del producto
            $table->string('codigo_sin', 20)->nullable();    // código SIN para factura
            $table->string('descripcion_sin', 200)->nullable(); // descripción para SIAT

            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['codigo', 'activo']);
        });
    }
    public function down(): void { Schema::dropIfExists('productos'); }
};
