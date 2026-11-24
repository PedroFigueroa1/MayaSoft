<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Almacen extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'almacenes';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'direccion',
        'responsable',
        'cuenta_id',
        'es_punto_venta',
        'es_produccion',
        'activo',
    ];

    protected $casts = [
        'es_punto_venta' => 'boolean',
        'es_produccion' => 'boolean',
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('empresa', function ($query) {
            if (auth()->check() && auth()->user()->empresa_id) {
                $query->where('empresa_id', auth()->user()->empresa_id);
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->empresa_id) {
                $model->empresa_id = auth()->user()->empresa_id;
            }
        });
    }

    public function cuenta()
    {
        return $this->belongsTo(Cuentas::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function getTipoAttribute(): string
    {
        if ($this->es_punto_venta && $this->es_produccion) return 'Venta + Producción';
        if ($this->es_punto_venta) return 'Punto de Venta';
        if ($this->es_produccion) return 'Producción';
        return 'Almacén General';
    }



    /**
     * Stock actual en este almacén
     */
    public function stockActual()
    {
        return $this->hasMany(StockActual::class, 'almacen_id');
    }

    /**
     * Movimientos de inventario en este almacén
     */
    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class, 'almacen_id');
    }

    /**
     * Cierres de caja de este almacén
     */
    public function cierresCaja()
    {
        return $this->hasMany(CierreCaja::class, 'almacen_id');
    }

    /**
     * Scope para puntos de venta
     */
    public function scopePuntosVenta($query)
    {
        return $query->where('es_punto_venta', true);
    }

    /**
     * Scope para almacenes de producción
     */
    public function scopeProduccion($query)
    {
        return $query->where('es_produccion', true);
    }

    /**
     * Scope para activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
