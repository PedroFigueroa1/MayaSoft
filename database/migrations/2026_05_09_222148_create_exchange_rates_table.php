<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('type');              // ufv, dolar_oficial, dolar_paralelo, euro, real, etc.
            $table->decimal('value', 16, 6);     // valor principal (venta para dólares, UFV)
            $table->decimal('buy_value', 16, 6)->nullable(); // compra (para dólares)
            $table->date('date');                // día al que corresponde el valor
            $table->timestamp('consulted_at');
            $table->timestamps();

            $table->unique(['type', 'date']);    // evita duplicados
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
