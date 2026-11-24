<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CentroCosto extends Model
{
    protected $table = 'centro_costos';

    protected $fillable = [
        'user_id',
        'codigo',
        'nombre',
        'descripcion',
        'activo',
        'es_base',
    ];

    protected $casts = [
        'activo'  => 'boolean',
        'es_base' => 'boolean',
        'user_id' => 'integer',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    // ✅ La firma DEBE ser scopeNombreDelScope(Builder $query, ...)
    public function scopeVisiblePara(Builder $query, $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('es_base', true);
        });
    }

    public function scopeDelUsuario(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBase(Builder $query): Builder
    {
        return $query->where('es_base', true)
                     ->whereNull('user_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function esDeSoloLectura(): bool
    {
        return $this->es_base && $this->user_id === null;
    }

    public function puedeEditar($userId): bool
    {
        return (int)$this->user_id === (int)$userId;
    }
}
