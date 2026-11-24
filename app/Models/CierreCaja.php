<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class CierreCaja extends Model
{
    protected $table = 'cierres_caja';

    protected $fillable = [
        'almacen_id', 'periodo_id', 'fecha',
        'total_ventas', 'total_facturas',
        'total_efectivo', 'total_tarjeta', 'total_qr', 'total_credito',
        'total_general', 'total_iva_df',
        'modo_contabilizacion', 'asiento_id', 'estado',
        'cerrado_por', 'cerrado_at',
        'contabilizado_por', 'contabilizado_at',
    ];

    protected $casts = [
        'fecha'              => 'date',
        'cerrado_at'         => 'datetime',
        'contabilizado_at'   => 'datetime',
        'total_efectivo'     => 'decimal:4',
        'total_tarjeta'      => 'decimal:4',
        'total_qr'           => 'decimal:4',
        'total_credito'      => 'decimal:4',
        'total_general'      => 'decimal:4',
        'total_iva_df'       => 'decimal:4',
    ];

    // ─── Relaciones ─────────────────────────────
    public function almacen(): BelongsTo       { return $this->belongsTo(Almacen::class); }
    public function periodo(): BelongsTo       { return $this->belongsTo(Periodo::class); }
    public function asiento(): BelongsTo       { return $this->belongsTo(Asiento::class); }
    public function cerradoPor(): BelongsTo    { return $this->belongsTo(User::class, 'cerrado_por'); }
    public function contabilizadoPor(): BelongsTo { return $this->belongsTo(User::class, 'contabilizado_por'); }

    // ─── Helpers que usa VentaController::store() ───

    /**
     * Devuelve (o crea) el cierre del día para el almacén/periodo indicado.
     */
    public static function obtenerHoy(int $almacenId, int $periodoId): self
    {
        return static::firstOrCreate(
            [
                'almacen_id' => $almacenId,
                'fecha'      => today()->toDateString(),
            ],
            [
                'periodo_id' => $periodoId,
                'estado'     => 'abierto',
            ]
        );
    }

    /**
     * Recalcula los totales del día a partir de las ventas confirmadas
     * y sus pagos.
     */
    public function recalcularTotales(): void
    {
        $base = Venta::where('almacen_id', $this->almacen_id)
            ->where('periodo_id', $this->periodo_id)
            ->whereDate('fecha', $this->fecha)
            ->where('estado', 'confirmada');

        $this->total_ventas    = (clone $base)->count();
        $this->total_facturas  = (clone $base)->where('con_factura', true)->count();
        $this->total_general   = (clone $base)->sum('total');
        $this->total_iva_df    = (clone $base)->sum('iva_df');

        // Totales por medio de pago
        $pagos = DB::table('pagos_venta')
            ->join('ventas', 'ventas.id', '=', 'pagos_venta.venta_id')
            ->where('ventas.almacen_id', $this->almacen_id)
            ->where('ventas.periodo_id', $this->periodo_id)
            ->whereDate('ventas.fecha', $this->fecha)
            ->where('ventas.estado', 'confirmada')
            ->select('pagos_venta.medio', DB::raw('SUM(pagos_venta.monto) as total'))
            ->groupBy('pagos_venta.medio')
            ->pluck('total', 'medio');

        $this->total_efectivo = (float) ($pagos['efectivo']         ?? 0);
        $this->total_tarjeta  = (float) ($pagos['tarjeta_debito']   ?? 0)
                              + (float) ($pagos['tarjeta_credito']  ?? 0);
        $this->total_qr       = (float) ($pagos['qr']               ?? 0);
        $this->total_credito  = (float) ($pagos['credito']          ?? 0);

        $this->save();
    }

    public function estaAbierto(): bool       { return $this->estado === 'abierto'; }
    public function estaCerrado(): bool       { return $this->estado === 'cerrado'; }
    public function estaContabilizado(): bool { return $this->estado === 'contabilizado'; }
}
