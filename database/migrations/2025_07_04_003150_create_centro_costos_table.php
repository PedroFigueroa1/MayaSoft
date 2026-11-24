<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('centro_costos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Insertar datos básicos
        DB::table('centro_costos')->insert([
            [
                'codigo' => 'ADM',
                'nombre' => 'Administración',
                'descripcion' => 'Gastos administrativos generales',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo' => 'VNT',
                'nombre' => 'Ventas',
                'descripcion' => 'Departamento comercial y ventas',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo' => 'MKT',
                'nombre' => 'Marketing',
                'descripcion' => 'Publicidad y promociones',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo' => 'PROD',
                'nombre' => 'Producción',
                'descripcion' => 'Costos de manufactura y producción',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo' => 'RH',
                'nombre' => 'Recursos Humanos',
                'descripcion' => 'Gastos de personal y nómina',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo' => 'TI',
                'nombre' => 'Tecnologías de Información',
                'descripcion' => 'Sistemas, equipos y software',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo' => 'FIN',
                'nombre' => 'Finanzas',
                'descripcion' => 'Departamento financiero y contable',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centro_costos');
    }
};
