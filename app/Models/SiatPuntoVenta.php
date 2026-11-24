<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SiatPuntoVenta extends Model
{
    protected $table = 'siat_puntos_venta';

    protected $fillable = [
        'empresa_id', 'configuracion_siat_id', 'nombre',
        'cod_sucursal', 'cod_punto_venta', 'direccion', 'municipio', 'telefono',
        'cuis', 'cuis_emitido_at', 'cuis_expira_at',
        'cufd_actual', 'cufd_control', 'cufd_emitido_at', 'cufd_expira_at',
        'correlativo_actual', 'fecha_correlativo', 'activo',
    ];

    protected $casts = [
        'cuis'              => 'encrypted',
        'cufd_actual'       => 'encrypted',
        'cufd_control'      => 'encrypted',
        'cuis_emitido_at'   => 'datetime',
        'cuis_expira_at'    => 'datetime',
        'cufd_emitido_at'   => 'datetime',
        'cufd_expira_at'    => 'datetime',
        'fecha_correlativo' => 'date',
        'activo'            => 'boolean',
    ];

    protected $hidden = ['cuis', 'cufd_actual', 'cufd_control'];

    // ─────────── Relaciones ───────────
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function configuracion(): BelongsTo
    {
        return $this->belongsTo(ConfiguracionSiat::class, 'configuracion_siat_id');
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'siat_punto_venta_user',
            'siat_punto_venta_id',
            'user_id'
        )->withPivot('por_defecto');
    }

    // ─────────── Helpers (los que te faltan) ───────────
    public function cuisVigente(): bool
    {
        return !empty($this->cuis)
            && $this->cuis_expira_at
            && $this->cuis_expira_at->isFuture();
    }

    public function cufdVigente(): bool
    {
        return !empty($this->cufd_actual)
            && $this->cufd_expira_at
            && $this->cufd_expira_at->isFuture();
    }

    public function siguienteCorrelativo(): int
    {
        $hoy = now()->toDateString();
        if (!$this->fecha_correlativo || $this->fecha_correlativo->toDateString() !== $hoy) {
            $this->correlativo_actual = 0;
            $this->fecha_correlativo  = $hoy;
        }
        $this->correlativo_actual++;
        $this->save();

        return $this->correlativo_actual;
    }
    /**
 * Devuelve el punto de venta marcado como "por_defecto" para el usuario.
 * Si no tiene ninguno marcado, retorna null (el caller decide el fallback).
 */
    public static function porDefectoPara(?User $user = null): ?self
    {
        $user ??= auth()->user();

        if (!$user) {
            return null;
        }

        return static::query()
            ->where('activo', true)
            ->whereHas('usuarios', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                ->where('por_defecto', true);
            })
            ->first();
    }

}
