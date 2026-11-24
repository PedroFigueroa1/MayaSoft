<?php

namespace App\Models;
use App\Models\CentroCosto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleComprobante extends Model
{
    protected $fillable = [
        'comprobante_id', 'producto_servicio', 'cantidad', 'unidad_medida',
        'precio_unitario', 'tasa_iva', 'tasa_it', 'tasa_ice', 'descuento',
        'subtotal', 'importe_iva', 'importe_it', 'importe_ice', 'total'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'tasa_iva' => 'decimal:2',
        'tasa_it' => 'decimal:2',
        'tasa_ice' => 'decimal:2',
        'descuento' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'importe_iva' => 'decimal:2',
        'importe_it' => 'decimal:2',
        'importe_ice' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public const UNIDADES_MEDIDA = [
        'UN' => 'Unidad',
        'KG' => 'Kilogramo',
        'M' => 'Metro',
        'L' => 'Litro',
        'GL' => 'Galón',
        'OT' => 'Otro'
    ];

    // Relaciones
    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class);
    }

}
