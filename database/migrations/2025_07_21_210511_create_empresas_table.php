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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('nit', 15)->unique();
            $table->text('direccion');
            $table->string('telefono', 20);
            $table->enum('moneda', ['BOB', 'USD'])->default('BOB');
            $table->string('actividad_economica', 100);
            $table->string('ciudad', 50);
            $table->string('departamento', 50);
            $table->string('logo')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};

