<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ============================================================
// DetalleVenta
// ============================================================
class DetalleVenta extends Model
{
    protected $table = 'detalle_ventas';

    protected $fillable = [
        'venta_id', 'producto_id',
        'cantidad', 'precio_unitario', 'descuento', 'subtotal', 'iva', 'total',
        'costo_unitario', 'costo_total', 'utilidad',
        'movimiento_id', 'lote_id',
    ];

    protected $casts = [
        'cantidad'        => 'decimal:4',
        'precio_unitario' => 'decimal:4',
        'descuento'       => 'decimal:4',
        'subtotal'        => 'decimal:4',
        'iva'             => 'decimal:4',
        'total'           => 'decimal:4',
        'costo_unitario'  => 'decimal:4',
        'costo_total'     => 'decimal:4',
        'utilidad'        => 'decimal:4',
    ];

    public function venta(): BelongsTo      { return $this->belongsTo(Venta::class); }
    public function producto(): BelongsTo   { return $this->belongsTo(Producto::class); }
    public function movimiento(): BelongsTo { return $this->belongsTo(MovimientoInventario::class, 'movimiento_id'); }
    public function lote(): BelongsTo       { return $this->belongsTo(LotePeps::class, 'lote_id'); }

    public function margenPorcentaje(): float
    {
        if ((float) $this->total <= 0) return 0.0;
        return round(((float) $this->utilidad / (float) $this->total) * 100, 2);
    }

    protected static function booted(): void
    {
        static::saving(function ($d) {
            $base          = round((float) $d->cantidad * (float) $d->precio_unitario, 4);
            $d->subtotal   = round($base - (float) $d->descuento, 4);
            // IVA incluido en Bolivia: IVA = subtotal × 13/113
            $d->iva        = round((float) $d->subtotal * 13 / 113, 4);
            $d->total      = $d->subtotal;
            $d->costo_total = round((float) $d->cantidad * (float) $d->costo_unitario, 4);
            $d->utilidad    = round((float) $d->total - (float) $d->costo_total, 4);
        });
    }
}

// ============================================================
// PagoVenta
// ============================================================
class PagoVenta extends Model
{
    protected $table = 'pagos_ventas';

    protected $fillable = [
        'venta_id', 'medio', 'monto', 'cambio',
        'fecha', 'referencia', 'cuenta_id',
    ];

    protected $casts = [
        'monto'  => 'decimal:4',
        'cambio' => 'decimal:4',
        'fecha'  => 'date',
    ];

    public function venta(): BelongsTo  { return $this->belongsTo(Venta::class); }
    public function cuenta(): BelongsTo { return $this->belongsTo(Cuentas::class, 'cuenta_id'); }

    public function medioLabel(): string
    {
        return match($this->medio) {
            'efectivo'        => 'Efectivo',
            'tarjeta_debito'  => 'Tarjeta débito',
            'tarjeta_credito' => 'Tarjeta crédito',
            'qr'              => 'QR',
            'transferencia'   => 'Transferencia',
            'cheque'          => 'Cheque',
            'credito'         => 'Crédito',
            default           => $this->medio,
        };
    }
}
