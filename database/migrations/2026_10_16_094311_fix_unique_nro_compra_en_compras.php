<?php
// ============================================================
// MIGRACIÓN CORRECTIVA — compras (IDEMPOTENTE)
// Cambia unicidad de nro_compra:
//   ANTES: unique(nro_compra) + unique(empresa_id, nro_compra)
//   AHORA: unique(empresa_id, periodo_id, nro_compra)
//
// Es idempotente: si un índice ya fue eliminado o creado,
// no vuelve a intentarlo. Útil porque la migración anterior
// falló a mitad de camino.
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Índices actuales de la tabla
        $indexes = collect(DB::select('SHOW INDEX FROM compras'))
            ->pluck('Key_name')
            ->unique()
            ->values();

        // 1) Índice simple sobre empresa_id (para liberar la FK)
        if (!$indexes->contains('compras_empresa_id_index')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->index('empresa_id', 'compras_empresa_id_index');
            });
        }

        // 2) Borrar unique global de nro_compra (si existe)
        if ($indexes->contains('compras_nro_compra_unique')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->dropUnique('compras_nro_compra_unique');
            });
        }

        // 3) Borrar unique(empresa_id, nro_compra) (si existe)
        if ($indexes->contains('compras_empresa_id_nro_compra_unique')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->dropUnique('compras_empresa_id_nro_compra_unique');
            });
        }

        // 4) Crear la nueva unicidad real (si no existe)
        if (!$indexes->contains('compras_empresa_periodo_nro_unique')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->unique(
                    ['empresa_id', 'periodo_id', 'nro_compra'],
                    'compras_empresa_periodo_nro_unique'
                );
            });
        }
    }

    public function down(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM compras'))
            ->pluck('Key_name')
            ->unique()
            ->values();

        if ($indexes->contains('compras_empresa_periodo_nro_unique')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->dropUnique('compras_empresa_periodo_nro_unique');
            });
        }

        if (!$indexes->contains('compras_empresa_id_nro_compra_unique')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->unique(['empresa_id', 'nro_compra']);
            });
        }

        if (!$indexes->contains('compras_nro_compra_unique')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->unique('nro_compra');
            });
        }

        if ($indexes->contains('compras_empresa_id_index')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->dropIndex('compras_empresa_id_index');
            });
        }
    }
};
