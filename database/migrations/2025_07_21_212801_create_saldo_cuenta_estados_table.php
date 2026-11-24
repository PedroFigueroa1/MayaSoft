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
        Schema::create('saldo_cuenta_estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estado_id')->constrained('estados_financieros')->onDelete('cascade');
            $table->foreignId('cuenta_id')->constrained('cuentas')->onDelete('cascade');
            $table->decimal('saldo_final', 15, 2);
            $table->string('nota', 255)->nullable();
            $table->timestamps();

            $table->unique(['estado_id', 'cuenta_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldo_cuenta_estados');
    }
};
