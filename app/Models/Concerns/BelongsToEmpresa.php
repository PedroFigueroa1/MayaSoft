<?php
namespace App\Models\Concerns;
 
use Illuminate\Database\Eloquent\Builder;
 
trait BelongsToEmpresa
{
    protected static function bootBelongsToEmpresa(): void
    {
        // Toda consulta queda filtrada por la empresa activa de la sesión
        static::addGlobalScope('empresa', function (Builder $q) {
            if ($id = session('empresa_id')) {
                $q->where($q->getModel()->getTable() . '.empresa_id', $id);
            }
        });
 
        // Todo registro nuevo hereda la empresa activa
        static::creating(function ($model) {
            $model->empresa_id ??= session('empresa_id');
        });
    }
}