<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleCompra extends Model
{
    protected $table = 'detalle_compras';

    protected $fillable = [
        'compra_id', 'producto_id',
        'cantidad', 'costo_unitario', 'descuento', 'subtotal',
        'movimiento_id',
    ];

    protected $casts = [
        'cantidad'       => 'decimal:4',
        'costo_unitario' => 'decimal:4',
        'descuento'      => 'decimal:4',
        'subtotal'       => 'decimal:4',
    ];

    public function compra(): BelongsTo    { return $this->belongsTo(Compra::class); }
    public function producto(): BelongsTo  { return $this->belongsTo(Producto::class); }
    public function movimiento(): BelongsTo { return $this->belongsTo(MovimientoInventario::class, 'movimiento_id'); }

    /** Recalcula el subtotal */
    public function calcularSubtotal(): float
    {
        return round((float) $this->cantidad * (float) $this->costo_unitario - (float) $this->descuento, 4);
    }

    protected static function booted(): void
    {
        // Recalcular subtotal antes de guardar
        static::saving(function ($detalle) {
            $detalle->subtotal = $detalle->calcularSubtotal();
        });
    }
}