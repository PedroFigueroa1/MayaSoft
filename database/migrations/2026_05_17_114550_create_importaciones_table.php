<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importaciones', function (Blueprint $table) {
            $table->id();

            // Relación con el período contable (obligatorio)
            $table->foreignId('periodo_id')
                ->constrained('periodos')
                ->onDelete('cascade');

            // Tipo de importación: compra o venta
            $table->enum('tipo', ['compra', 'venta'])
                ->comment('Tipo de libro importado: compra o venta');

            // Datos del archivo original
            $table->string('nombre_archivo', 255)
                ->comment('Nombre original del archivo importado');
            $table->string('ruta_archivo', 500)
                ->nullable()
                ->comment('Ruta física donde se almacena el archivo (opcional)');
            $table->bigInteger('tamano_bytes')
                ->nullable()
                ->comment('Tamaño del archivo en bytes');

            // Mes contable (puede ser redundante con periodo_id, pero se guarda para facilitar consultas)
            $table->string('mes', 20)
                ->nullable()
                ->comment('Mes del archivo (ej: Enero, Febrero) o formato YYYY-MM');

            // Estado del proceso de importación
            $table->enum('estado', ['pendiente', 'procesando', 'completado', 'fallido'])
                ->default('pendiente')
                ->comment('Estado actual de la importación');

            // Estadísticas de procesamiento
            $table->unsignedInteger('total_registros')
                ->default(0)
                ->comment('Cantidad total de filas leídas en el archivo');
            $table->unsignedInteger('registros_exitosos')
                ->default(0)
                ->comment('Registros que se guardaron correctamente en la tabla correspondiente');
            $table->unsignedInteger('registros_fallidos')
                ->default(0)
                ->comment('Registros con errores que no pudieron importarse');
            $table->text('log_error')
                ->nullable()
                ->comment('Mensajes de error o advertencias generados durante la importación');

            // Auditoría
            $table->foreignId('importado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que ejecutó la importación');

            $table->timestamps();  // created_at = fecha/hora de importación

            // Índices para búsquedas frecuentes
            $table->index(['periodo_id', 'tipo']);
            $table->index('estado');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones');
    }
};
