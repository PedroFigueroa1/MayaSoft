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
        Schema::create('documento_soportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobante_id')->constrained('comprobantes')->onDelete('cascade');
            $table->enum('tipo', [
                'FAC', 'ND', 'NC', 'REC', 'CON',
                'CHQ', 'DEP', 'ORD', 'REM', 'OTR'
            ]);
            $table->string('numero', 50);
            $table->date('fecha');
            $table->string('emisor', 100);
            $table->string('archivo');
            $table->text('descripcion')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['fecha', 'numero']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documento_soportes');
    }
};
