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
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 15)->unique();
            $table->string('nombre', 100);
            $table->enum('tipo', ['A', 'P', 'PN', 'I', 'G']);
            $table->unsignedTinyInteger('nivel');
            $table->boolean('permite_movimiento')->default(true);
            $table->foreignId('cuenta_padre_id')->nullable()->constrained('cuentas')->onDelete('cascade');
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index('codigo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
