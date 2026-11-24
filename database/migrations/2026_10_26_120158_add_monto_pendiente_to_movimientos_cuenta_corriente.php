<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_cuenta_corriente', function (Blueprint $table) {
            // Nuevas columnas para aplicar abonos FIFO contra cargos
            $table->decimal('monto_pendiente', 14, 2)->default(0)->after('monto');
            $table->decimal('monto_aplicado', 14, 2)->default(0)->after('monto_pendiente');

            // Índice para consultas de pendientes / aging
            $table->index(
                ['proveedor_cliente_id', 'estado', 'fecha_vencimiento'],
                'mcc_pc_estado_venc_idx'
            );
        });

        // Backfill: cargos pendientes/parciales → monto_pendiente = monto - monto_aplicado (aquí 0)
        DB::table('movimientos_cuenta_corriente')
            ->where('tipo', 'cargo')
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->update(['monto_pendiente' => DB::raw('monto')]);

        // Abonos ya pagados → monto_pendiente = 0 (default ya lo pone)
        DB::table('movimientos_cuenta_corriente')
            ->where('tipo', 'abono')
            ->update(['monto_pendiente' => 0]);
    }

    public function down(): void
    {
        Schema::table('movimientos_cuenta_corriente', function (Blueprint $table) {
            $table->dropIndex('mcc_pc_estado_venc_idx');
            $table->dropColumn(['monto_pendiente', 'monto_aplicado']);
        });
    }
};
