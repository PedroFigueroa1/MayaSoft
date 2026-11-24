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
        Schema::create('detalle_asientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asiento_id')->constrained('asientos')->onDelete('cascade');
            $table->foreignId('cuenta_id')->constrained('cuentas')->onDelete('restrict');
            $table->decimal('debe', 15, 2)->default(0);
            $table->decimal('haber', 15, 2)->default(0);
            $table->string('descripcion', 255)->nullable();

            // NUEVAS COLUMNAS AGREGADAS
            $table->foreignId('centro_costos_id')
                  ->nullable()
                  ->constrained('centro_costos')
                  ->onDelete('restrict');
            $table->boolean('deducible')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_asientos');
    }
};
