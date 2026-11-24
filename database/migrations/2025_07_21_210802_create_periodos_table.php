<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('periodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('nombre', 20);
            $table->date('fecha_inicio');
            $table->date('fecha_cierre');
            $table->enum('estado', ['A', 'C'])->default('A');
            $table->foreignId('cerrado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('fecha_cierre_real')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodos');
    }
};
