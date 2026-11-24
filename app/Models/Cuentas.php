<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Cuentas;

class Cuentas extends Model
{
    use HasFactory;
    protected $table = 'cuentas';

    protected $fillable = [
        'user_id',
        'codigo',
        'nombre',
        'tipo',
        'nivel',
        'permite_movimiento',
        'cuenta_padre_id',
        'descripcion',
        'es_base',
    ];

    protected $casts = [
        'permite_movimiento' => 'boolean',
        'es_base'            => 'boolean',
        'nivel'              => 'integer',
    ];

    // Tipos de cuenta
    const TIPOS = [
        'A' => 'Activo',
        'P' => 'Pasivo',
        'PN' => 'Patrimonio Neto',
        'I' => 'Ingreso',
        'G' => 'Gasto',
    ];

    public function movimientos()
    {
        return $this->hasMany(DetalleAsiento::class, 'cuenta_id');
    }


    public function hijos(): HasMany
    {
        return $this->hasMany(Cuenta::class, 'cuenta_padre_id');
    }




    // Relación con cuenta padre
    public function padre()
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_padre_id');
    }

    // Relación con subcuentas
    public function subcuentas()
    {
        return $this->hasMany(Cuentas::class, 'cuenta_padre_id');
    }

    // Verificar si es cuenta padre
    public function esCuentaPadre()
    {
        return $this->subcuentas()->exists();
    }

    // Obtener el nombre completo del tipo
    public function getTipoNombreAttribute()
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    // Obtener saldo actual (implementar según tu lógica de asientos)
    public function saldoActual($periodoId)
    {
        // Implementar lógica similar a la de Django
        // Esto es un ejemplo básico
        $debe = $this->movimientos()->where('periodo_id', $periodoId)->sum('debe');
        $haber = $this->movimientos()->where('periodo_id', $periodoId)->sum('haber');

        if (in_array($this->tipo, ['A', 'G'])) {
            return $debe - $haber;
        } else {
            return $haber - $debe;
        }
    }
    public function cuenta()
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_id');
    }


    public function detalleAsientos()
{
    return $this->hasMany(DetalleAsiento::class, 'cuenta_id');
}

        // Relación con los asientos a través de la tabla intermedia
public function asientos()
{
    return $this->hasManyThrough(
        Asiento::class,
        DetalleAsiento::class,
        'cuenta_id',    // detalle_asientos.cuenta_id → cuentas.id
        'id',           // asientos.id
        'id',           // cuentas.id
        'asiento_id'    // detalle_asientos.asiento_id → asientos.id
    );
}

    public function scopeMayor($query)
    {
        return $query->where('permite_movimiento', true);
    }

    /**
     * Scope para cuentas por tipo
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    //para importacion
        /**
     * Determina el tipo de cuenta basado en el nivel y código
     */
    public static function determinarTipo($codigo, $nombre)
    {
        // Determinar tipo basado en el nombre o código
        $nombre = strtoupper($nombre);

        if (str_starts_with($codigo, '1')) {
            return 'A'; // Activo
        } elseif (str_starts_with($codigo, '2')) {
            return 'P'; // Pasivo
        } elseif (str_starts_with($codigo, '3')) {
            return 'PN'; // Patrimonio Neto
        } elseif (str_starts_with($codigo, '4')) {
            return 'I'; // Ingreso
        } elseif (str_starts_with($codigo, '5')) {
            return 'G'; // Gastos
        }

        // Fallback basado en el nombre
        if (str_contains($nombre, 'ACTIVO')) return 'A';
        if (str_contains($nombre, 'PASIVO')) return 'P';
        if (str_contains($nombre, 'PATRIMONIO')) return 'PN';
        if (str_contains($nombre, 'INGRESO') || str_contains($nombre, 'VENTA')) return 'I';
        if (str_contains($nombre, 'GASTO') || str_contains($nombre, 'COSTO')) return 'G';

        return 'A'; // Por defecto
    }

    /**
     * Determina si permite movimiento (solo las cuentas de último nivel)
     */
    public static function determinarPermiteMovimiento($codigo)
    {
        // Cuenta de último nivel si tiene todos los segmentos completos
        // Ejemplo: 1-1-1-001-001 es nivel 5 y permite movimiento
        $segmentos = explode('-', str_replace('.', '-', $codigo));
        return count($segmentos) == 5; // Ajusta según tu estructura
    }
    /**
     * Relación recursiva con todas las cuentas hijas
     */
    public function hijosRecursivos()
    {
        return $this->hijos()->with('hijosRecursivos');
    }

    /**
     * Obtener todos los ancestros de la cuenta
     */
    public function ancestros()
    {
        $ancestros = collect();
        $padre = $this->padre;

        while ($padre) {
            $ancestros->push($padre);
            $padre = $padre->padre;
        }

        return $ancestros->reverse();
    }

    /**
     * Obtener la ruta completa de la cuenta
     */
    public function getRutaAttribute()
    {
        $ruta = $this->ancestros()->pluck('nombre')->toArray();
        $ruta[] = $this->nombre;

        return implode(' > ', $ruta);
    }

    /**
     * Obtener el código del padre basado en el código actual
     */
    public static function obtenerCodigoPadre($codigo)
    {
        $partes = explode('.', $codigo);

        if (count($partes) <= 1) {
            return null;
        }

        array_pop($partes);
        return implode('.', $partes);
    }

    /**
     * Determinar el tipo de cuenta basado en el código
     */

    /**
     * Determinar el nivel basado en el código
     */
    public static function determinarNivel($codigo)
    {
        return count(explode('.', $codigo));
    }

    /**
     * Scope para obtener cuentas por nivel
     */
    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    /**
     * Scope para obtener cuentas que permiten movimiento
     */
    public function scopePermiteMovimiento($query)
    {
        return $query->where('permite_movimiento', true);
    }

    /**
     * Scope para buscar por código o nombre
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where(function($q) use ($termino) {
            $q->where('codigo', 'LIKE', "%{$termino}%")
              ->orWhere('nombre', 'LIKE', "%{$termino}%");
        });
    }

       public function scopeVisiblePara(Builder $query, $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('es_base', true);
        });
    }

    // Solo las cuentas propias del usuario
    public function scopeDelUsuario(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // Solo cuentas base
    public function scopeBase(Builder $query): Builder
    {
        return $query->where('es_base', true)->whereNull('user_id');
    }


    // ==========================================
    // HELPERS
    // ==========================================

    // La cuenta pertenece a un usuario específico
    public function esPropia(): bool
    {
        return $this->user_id !== null;
    }

    // La cuenta es base y nadie puede editarla (solo admin)
    public function esDeSoloLectura(): bool
    {
        return $this->es_base && $this->user_id === null;
    }

    // El usuario dado puede editar esta cuenta
    public function puedeEditar($userId): bool
    {
        return $this->user_id === $userId;
    }

    // El usuario dado puede eliminar esta cuenta
    public function puedeEliminar($userId): bool
    {
        return $this->user_id === $userId;
    }

    // Tiene subcuentas hijas
    public function tieneSubcuentas(): bool
    {
        return $this->subcuentas()->exists();
    }
}
