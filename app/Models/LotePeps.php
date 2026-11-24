<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotePeps extends Model
{
    protected $table = 'lotes_peps';

    protected $fillable = [
        'producto_id',
        'almacen_id',
        'cantidad_inicial',
        'cantidad_disponible',
        'costo_unitario',
        'fecha_entrada',
        'fecha_vencimiento',
        'movimiento_origen_id',
        'agotado',
    ];

    protected $casts = [
        'cantidad_inicial' => 'decimal:4',
        'cantidad_disponible' => 'decimal:4',
        'costo_unitario' => 'decimal:4',
        'fecha_entrada' => 'date',
        'fecha_vencimiento' => 'date',
        'agotado' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function movimientoOrigen(): BelongsTo
    {
        return $this->belongsTo(
            MovimientoInventario::class,
            'movimiento_origen_id'
        );
    }
}
