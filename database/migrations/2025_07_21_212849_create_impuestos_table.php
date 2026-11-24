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
        Schema::create('impuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('codigo', 10);
            $table->string('nombre', 100);
            $table->enum('tipo', ['IVA', 'IT', 'ICE', 'RCIVA', 'OTR']);
            $table->decimal('tasa', 5, 2);
            $table->foreignId('cuenta_debito_id')->nullable()->constrained('cuentas')->onDelete('set null');
            $table->foreignId('cuenta_credito_id')->nullable()->constrained('cuentas')->onDelete('set null');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo']);
            $table->index(['tipo', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impuestos');
    }
};
