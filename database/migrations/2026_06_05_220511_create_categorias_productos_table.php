<?php
// ============================================================
// MIGRACIÓN 1 — categorias_productos
// Árbol de categorías (padre-hijo) para organizar el catálogo
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categorias_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 100);
            $table->unsignedBigInteger('parent_id')->nullable(); // subcategorías
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')
                  ->references('id')->on('categorias_productos')
                  ->nullOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('categorias_productos'); }
};
