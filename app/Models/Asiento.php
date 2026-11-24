<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asiento extends Model
{
    protected $fillable = [
        'periodo_id',
        'fecha',
        'numero_asiento',
        'tipo',
        'glosa',
        'comprobante',
        'creado_por'
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // Relaciones

    public function periodo()
{
    return $this->belongsTo(Periodo::class, 'periodo_id');
}


    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function comprobanteRelacionado(): HasOne
    {
        return $this->hasOne(Comprobante::class, 'asiento_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleAsiento::class);
    }


    // Aliases para compatibilidad (opcional)
    public function movimientos(): HasMany
    {
        return $this->detalles();
    }

    // Métodos para calcular totales
    public function getTotalDebeAttribute(): float
    {
        return (float) $this->detalles()->sum('debe');
    }

    public function getTotalHaberAttribute(): float
    {
        return (float) $this->detalles()->sum('haber');
    }

    public function getDiferenciaAttribute(): float
    {
        return abs($this->total_debe - $this->total_haber);
    }

    // Métodos de validación
    public function estaBalanceado(): bool
    {
        return round($this->total_debe, 2) === round($this->total_haber, 2);
    }

    // Métodos de operación
    public function agregarMovimiento(array $datos): DetalleAsiento
    {
        return $this->detalles()->create($datos);
    }

    public function agregarMovimientos(array $movimientos): void
    {
        foreach ($movimientos as $movimiento) {
            $this->agregarMovimiento($movimiento);
        }
    }
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
    public function creado_por()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
