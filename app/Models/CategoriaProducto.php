<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoriaProducto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categorias_productos';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'parent_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ─── RELACIONES ───

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function padre()
    {
        return $this->belongsTo(CategoriaProducto::class, 'parent_id');
    }

    public function hijos()
    {
        return $this->hasMany(CategoriaProducto::class, 'parent_id');
    }

    public function descendientes()
    {
        return $this->hasMany(CategoriaProducto::class, 'parent_id')->with('descendientes');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    // ─── SCOPES ───

    /**
     * ✅ Filtrar por empresa
     */
    public function scopeDeEmpresa($query, $empresaId)
    {
        if ($empresaId) {
            return $query->where('empresa_id', $empresaId);
        }
        return $query;
    }

    public function scopeRaices($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // ─── MÉTODOS ESTÁTICOS (adaptados para empresa) ───

    /**
     * Árbol filtrado por empresa
     */
    public static function arbol($empresaId = null)
    {
        $query = self::deEmpresa($empresaId)  // ✅ filtra empresa
            ->raices()
            ->with(['descendientes' => function ($q) use ($empresaId) {
                // ✅ Los descendientes también deben filtrar por empresa
                if ($empresaId) {
                    $q->where('empresa_id', $empresaId);
                }
                $q->withCount('productos');
            }])
            ->withCount('productos')
            ->activos();

        return $query->get();
    }

    /**
     * Lista plana filtrada por empresa
     */
    public static function listaPlana($empresaId = null, $padreId = null, $nivel = 0, &$resultado = [])
    {
        $query = self::where('parent_id', $padreId)
            ->deEmpresa($empresaId)  // ✅ filtra empresa
            ->activos()
            ->orderBy('nombre');

        $categorias = $query->get();

        foreach ($categorias as $cat) {
            $resultado[] = [
                'id'     => $cat->id,
                'nombre' => str_repeat('— ', $nivel) . $cat->nombre,
                'codigo' => $cat->codigo,
                'nivel'  => $nivel,
            ];
            self::listaPlana($empresaId, $cat->id, $nivel + 1, $resultado);
        }

        return $resultado;
    }
}
