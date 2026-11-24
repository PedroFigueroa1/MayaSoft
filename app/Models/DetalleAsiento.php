<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleAsiento extends Model
{
    protected $table = 'detalle_asientos';

    protected $fillable = [
        'asiento_id',
        'cuenta_id',
        'debe',
        'haber',
        'descripcion',
        'centro_costos_id',
        'deducible',
    ];

    protected $casts = [
        'debe' => 'float',
        'haber' => 'float',
        'deducible' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    protected $attributes = [
        'debe' => 0,
        'haber' => 0,
        'deducible' => false, // Valor por defecto
    ];

    public function cuenta()
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_id');
    }


    public function asiento()
    {
        return $this->belongsTo(Asiento::class, 'asiento_id');
    }


    // Métodos auxiliares
    public function getMontoAttribute()
    {
        return $this->debe > 0 ? $this->debe : $this->haber;
    }

    public function getTipoMovimientoAttribute(): string
    {
        return $this->debe > 0 ? 'DEBE' : 'HABER';
    }

    //nuevos métodos para relaciones
    public function centroCosto()
    {
        return $this->belongsTo(CentroCosto::class, 'centro_costos_id');
    }

    /**
     * Scope para filtrar registros deducibles
     */
    public function scopeDeducibles($query)
    {
        return $query->where('deducible', true);
    }

    /**
     * Scope para filtrar registros no deducibles
     */
    public function scopeNoDeducibles($query)
    {
        return $query->where('deducible', false);
    }

    /**
     * Scope para filtrar por centro de costos
     */
    public function scopePorCentroCosto($query, $centroCostosId)
    {
        return $query->where('centro_costos_id', $centroCostosId);
    }

    /**
     * Scope para filtrar por múltiples centros de costos
     */
    public function scopeEnCentrosCostos($query, array $centrosCostosIds)
    {
        return $query->whereIn('centro_costos_id', $centrosCostosIds);
    }

    /**
     * Scope para registros con débito mayor a 0
     */
    public function scopeConDebe($query)
    {
        return $query->where('debe', '>', 0);
    }

    /**
     * Scope para registros con haber mayor a 0
     */
    public function scopeConHaber($query)
    {
        return $query->where('haber', '>', 0);
    }

    /**
     * Obtener el monto total (débito - crédito)
     */
    public function getMontoTotalAttribute()
    {
        return $this->debe - $this->haber;
    }

    /**
     * Verificar si el registro tiene centro de costos asignado
     */
    public function getTieneCentroCostosAttribute()
    {
        return !is_null($this->centro_costos_id);
    }

    /**
     * Obtener la descripción completa con información adicional
     */
    public function getDescripcionCompletaAttribute()
    {
        $descripcion = $this->descripcion;

        if ($this->tiene_centro_costos) {
            $descripcion .= " [Centro: {$this->centroCosto->codigo}]";
        }

        if ($this->deducible) {
            $descripcion .= " [Deducible]";
        }

        return $descripcion;
    }
    public function centro_costo()
    {
        return $this->belongsTo(CentroCosto::class, 'centro_costos_id');
    }
    public function creado_por()
    {
        return $this->belongsTo(User::class);
        // o 'creado_por_id' si tu columna se llama así
    }
    public function lineas()
    {
        return $this->hasMany(DetalleAsiento::class, 'asiento_id');
    }


}
