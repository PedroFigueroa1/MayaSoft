<?php
// ============================================================
// MIGRACIÓN 16 — configuracion_siat
// Credenciales y parámetros de conexión con la API SIAT del SIN
// Bolivia. Una fila por empresa / punto de emisión.
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('configuracion_siat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
 
            // NIT y razón social salen de empresas; no se duplican aquí.
            $table->string('cod_ambiente', 5)->default('2');   // 1=producción, 2=piloto
            $table->string('cod_modalidad', 5)->default('2');  // verificar contra la doc. vigente del SIN
            $table->string('cod_tipo_factura', 5)->default('1');
            $table->string('cod_sistema', 100)->nullable();    // código de sistema que da el SIN
 
            $table->text('token_delegado');                    // se guarda CIFRADO (cast 'encrypted')
            $table->timestamp('token_expira_at')->nullable();
            $table->string('url_api', 200)->nullable();
 
            $table->boolean('activo')->default(true);
            $table->timestamps();
 
            // Una config por empresa y ambiente (puedes tener piloto y producción a la vez)
            $table->unique(['empresa_id', 'cod_ambiente']);
        });
 
        // --------------------------------------------------------
        // Nivel PUNTO DE VENTA: CUIS, CUFD y correlativo propios.
        // --------------------------------------------------------
        Schema::create('siat_puntos_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('configuracion_siat_id')->constrained('configuracion_siat')->cascadeOnDelete();
 
            $table->string('nombre', 100);                     // "Caja 1 - Casa matriz"
            $table->integer('cod_sucursal')->default(0);       // 0 = casa matriz
            $table->integer('cod_punto_venta')->default(0);
            $table->string('direccion', 250)->nullable();      // sale impresa en la factura
            $table->string('municipio', 100)->nullable();
            $table->string('telefono', 30)->nullable();
 
            $table->string('cuis', 200)->nullable();
            $table->timestamp('cuis_emitido_at')->nullable();
            $table->timestamp('cuis_expira_at')->nullable();
 
            $table->text('cufd_actual')->nullable();
            $table->text('cufd_control')->nullable();
            $table->timestamp('cufd_emitido_at')->nullable();
            $table->timestamp('cufd_expira_at')->nullable();
 
            $table->integer('correlativo_actual')->default(0);
            $table->date('fecha_correlativo')->nullable();
 
            $table->boolean('activo')->default(true);
            $table->timestamps();
 
            $table->unique(['configuracion_siat_id', 'cod_sucursal', 'cod_punto_venta'], 'uq_siat_punto');
        });
 
        // Qué usuario puede facturar en qué punto de venta
        Schema::create('siat_punto_venta_user', function (Blueprint $table) {
            $table->foreignId('siat_punto_venta_id')->constrained('siat_puntos_venta')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('por_defecto')->default(false);
            $table->primary(['siat_punto_venta_id', 'user_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('siat_punto_venta_user');
        Schema::dropIfExists('siat_puntos_venta');
        Schema::dropIfExists('configuracion_siat');
    }
};
