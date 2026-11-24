<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnidadMedida extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'unidades_medida';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Productos que usan esta unidad de medida
     */
    public function productos()
    {
        return $this->hasMany(Producto::class, 'unidad_medida_id');
    }

    /**
     * Scope para activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
