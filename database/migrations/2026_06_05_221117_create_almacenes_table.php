<?php
// ============================================================
// MIGRACIÓN 5 — almacenes
// Depósitos o ubicaciones físicas de inventario.
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('almacenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 100);
            $table->string('direccion', 200)->nullable();
            $table->string('responsable', 100)->nullable();

            // Cuenta contable del almacén
            // (si todos usan la misma cuenta, traspasos no generan asiento)
            $table->foreignId('cuenta_id')
                  ->nullable()->constrained('cuentas')->nullOnDelete();

            // Comportamiento
            $table->boolean('es_punto_venta')->default(false); // habilita POS
            $table->boolean('es_produccion')->default(false);  // recibe productos terminados
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('almacenes'); }
};
