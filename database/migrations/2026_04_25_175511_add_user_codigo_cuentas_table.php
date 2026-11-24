
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable();

            $table->unique(
                ['codigo', 'user_id'],
                'cuentas_codigo_user_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            $table->dropUnique('cuentas_codigo_user_unique');
            $table->dropColumn('user_id');
        });
    }
};
