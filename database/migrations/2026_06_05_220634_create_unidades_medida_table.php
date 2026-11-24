<?php
// ============================================================
// MIGRACIÓN 2 — unidades_medida
// Pza, Kg, Lt, Mt, Caja, etc.
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('unidades_medida', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo', 10)->unique();  // Pza, Kg, Lt...
            $table->string('nombre', 60);
            $table->string('descripcion', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('unidades_medida'); }
};
