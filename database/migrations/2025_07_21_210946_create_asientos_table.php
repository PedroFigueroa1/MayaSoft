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
        Schema::create('asientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos')->onDelete('restrict');
            $table->date('fecha');
            $table->string('numero_asiento', 20);
            $table->enum('tipo', ['ING', 'EGR', 'TRN', 'AJT', 'CIE'])->default('TRN');
            $table->text('glosa');
            $table->string('comprobante', 50)->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['periodo_id', 'numero_asiento']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asientos');
    }
};
