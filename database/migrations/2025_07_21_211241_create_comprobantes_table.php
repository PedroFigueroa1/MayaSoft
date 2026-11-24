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
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos')->onDelete('restrict');
            $table->foreignId('asiento_id')->nullable()->constrained('asientos')->onDelete('cascade');
            $table->enum('tipo', [
                'FAC', 'FAC_E', 'REC', 'ND', 'NC',
                'LQD', 'CON', 'ORD', 'PAG', 'AJT'
            ]);
            $table->string('numero', 50);
            $table->date('fecha_emision');
            $table->date('fecha_limite_emision')->nullable();
            $table->string('lugar_emision', 100);
            $table->string('numero_autorizacion', 100)->nullable();
            $table->string('codigo_control', 100)->nullable();
            $table->text('qr')->nullable();
            $table->foreignId('emisor_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('receptor_id')->constrained('terceros')->onDelete('cascade');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('descuento', 15, 2)->default(0);
            $table->decimal('importe_base', 15, 2)->default(0);
            $table->decimal('importe_ice', 15, 2)->default(0);
            $table->decimal('importe_exento', 15, 2)->default(0);
            $table->decimal('importe_iva', 15, 2)->default(0);
            $table->decimal('importe_it', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('leyenda');
            $table->string('archivo_pdf');
            $table->string('archivo_xml')->nullable();
            $table->enum('estado_sin', [
                'NO_ENVIADO',
                'PENDIENTE',
                'VALIDADO',
                'ANULADO',
                'ERROR'
            ])->default('NO_ENVIADO');
            $table->foreignId('creado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['emisor_id', 'tipo', 'numero']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
