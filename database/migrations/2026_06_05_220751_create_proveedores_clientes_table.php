<?php
// ============================================================
// MIGRACIÓN 3 — proveedores_clientes
// Tabla unificada: un NIT puede ser proveedor, cliente o ambos.
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('proveedores_clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();

            $table->string('codigo', 30);                          // NUEVO
            $table->enum('tipo_documento', ['NIT','CI','CE','PASAPORTE','OD'])->default('NIT'); // NUEVO
            $table->string('nit', 20)->nullable();
            $table->string('ci', 15)->nullable();
            $table->string('complemento', 5)->nullable();
            $table->string('razon_social', 200);
            $table->string('nombre_comercial', 200)->nullable();

            $table->boolean('es_proveedor')->default(false);
            $table->boolean('es_cliente')->default(false);

            $table->string('email', 150)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('direccion', 250)->nullable();
            $table->string('ciudad', 80)->nullable();
            $table->enum('departamento', [
                'La Paz','Santa Cruz','Cochabamba','Oruro','Potosí',
                'Chuquisaca','Tarija','Beni','Pando'
            ])->nullable();
            $table->string('pais', 60)->default('Bolivia');        // NUEVO

            $table->foreignId('cuenta_por_cobrar_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('cuenta_por_pagar_id')->nullable()->constrained('cuentas')->nullOnDelete();

            $table->integer('dias_credito')->default(0);
            $table->decimal('limite_credito', 15, 2)->default(0);

            $table->boolean('activo')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // NUEVO
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); // NUEVO
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete(); // NUEVO

            $table->timestamps();
            $table->softDeletes();

            // Unicidad por empresa
            $table->unique(['empresa_id', 'codigo']);
            $table->unique(['empresa_id', 'nit']);
            $table->unique(['empresa_id', 'ci']);

            // Índices de listado
            $table->index(['empresa_id', 'activo']);
            $table->index(['empresa_id', 'razon_social']);
            $table->index(['empresa_id', 'es_proveedor']);
            $table->index(['empresa_id', 'es_cliente']);
        });
    }
    public function down(): void { Schema::dropIfExists('proveedores_clientes'); }
};
