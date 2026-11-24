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
        Schema::create('reportes_personalizados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->enum('tipo', [
                'BAL_COMP',
                'AUX_CTA',
                'CONC_BANC',
                'ANAL_CTA',
                'MOV_TERC'
            ]);
            $table->json('parametros');
            $table->foreignId('creado_por_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('periodo_id')->constrained('periodos')->onDelete('cascade');
            $table->string('archivo')->nullable();
            $table->timestamps();

            $table->index('created_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes_personalizados');
    }
};
