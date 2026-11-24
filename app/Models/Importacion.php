<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Importacion extends Model
{
    use HasFactory;

    protected $table = 'importaciones';

    protected $fillable = [
        'periodo_id',
        'tipo',
        'nombre_archivo',
        'ruta_archivo',
        'tamano_bytes',
        'mes',
        'estado',
        'total_registros',
        'registros_exitosos',
        'registros_fallidos',
        'log_error',
        'importado_por',
    ];

    protected $casts = [
        'tamano_bytes' => 'integer',
        'total_registros' => 'integer',
        'registros_exitosos' => 'integer',
        'registros_fallidos' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function importador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'importado_por');
    }


    // Relación con el usuario que importó (clave foránea: importado_por)
    public function importadoPor()
    {
        return $this->belongsTo(User::class, 'importado_por');
    }
    public function libroCompras()
    {
        return $this->hasMany(LibroCompra::class, 'importacion_id');
    }

    public function compras(): HasMany
    {
        return $this->hasMany(LibroCompra::class, 'importacion_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(LibroVenta::class, 'importacion_id');
    }

    // Scope para filtrar por tipo
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // Helper para obtener el total de registros procesados
    public function getTotalProcesadosAttribute(): int
    {
        return $this->registros_exitosos + $this->registros_fallidos;
    }

    // Helper para saber si se completó correctamente
    public function isCompletado(): bool
    {
        return $this->estado === 'completado' && $this->total_registros === $this->totalProcesados;
    }
}
