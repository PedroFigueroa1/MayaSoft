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
        Schema::create('libros_contables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos')->onDelete('cascade');
            $table->enum('tipo', [
                'DIARIO', 'MAYOR', 'VENTAS',
                'COMPRAS', 'INVENTARIO', 'BANCOS'
            ]);
            $table->timestamp('fecha_generacion')->useCurrent();
            $table->foreignId('generado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->string('archivo_pdf');
            $table->string('archivo_excel')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['periodo_id', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('libros_contables');
    }
};
