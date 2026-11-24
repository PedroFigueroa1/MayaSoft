<?php
// ============================================================
// MIGRACIÓN CORRECTIVA — ventas
// ------------------------------------------------------------
// Problemas que corrige:
//   1) Falta empresa_id (bug multi-empresa).
//   2) nro_venta era unique() GLOBAL → impedía repetir VT-0001
//      entre empresas y entre períodos.
//
// Resultado:
//   - empresa_id agregado (NOT NULL) y poblado desde periodos.
//   - Unicidad real: (empresa_id, periodo_id, nro_venta).
// ============================================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ── 1) Agregar empresa_id (nullable primero) ───────────
        if (!Schema::hasColumn('ventas', 'empresa_id')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->foreignId('empresa_id')
                      ->nullable()
                      ->after('id')
                      ->constrained('empresas')
                      ->cascadeOnDelete();
            });

            // Poblar desde periodos (asumiendo que periodos.empresa_id existe)
            DB::statement('
                UPDATE ventas v
                JOIN periodos p ON p.id = v.periodo_id
                SET v.empresa_id = p.empresa_id
                WHERE v.empresa_id IS NULL
            ');

            // Convertir a NOT NULL
            Schema::table('ventas', function (Blueprint $table) {
                $table->foreignId('empresa_id')->nullable(false)->change();
            });
        }

        // ── 2) Quitar unique global de nro_venta ───────────────
        //    Nombre autogenerado por Laravel: ventas_nro_venta_unique
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique('ventas_nro_venta_unique');
        });

        // ── 3) Nueva unicidad real ─────────────────────────────
        Schema::table('ventas', function (Blueprint $table) {
            $table->unique(
                ['empresa_id', 'periodo_id', 'nro_venta'],
                'ventas_empresa_periodo_nro_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique('ventas_empresa_periodo_nro_unique');
            $table->unique('nro_venta');
        });

        if (Schema::hasColumn('ventas', 'empresa_id')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropConstrainedForeignId('empresa_id');
            });
        }
    }
};
