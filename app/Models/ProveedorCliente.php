<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProveedorCliente extends Model
{
    use SoftDeletes;

    protected $table = 'proveedores_clientes';

    protected $fillable = [
        'empresa_id', 'nit', 'ci', 'complemento', 'razon_social', 'nombre_comercial',
        'es_proveedor', 'es_cliente',
        'email', 'telefono', 'direccion', 'ciudad', 'departamento',
        'cuenta_por_cobrar_id', 'cuenta_por_pagar_id',
        'dias_credito', 'limite_credito', 'activo',
    ];

    protected $casts = [
        'es_proveedor'   => 'boolean',
        'es_cliente'     => 'boolean',
        'activo'         => 'boolean',
        'limite_credito' => 'decimal:2',
    ];

    // ── Relaciones ──────────────────────────────────────────────
    public function cuentaPorCobrar(): BelongsTo
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_por_cobrar_id');
    }

    public function cuentaPorPagar(): BelongsTo
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_por_pagar_id');
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'proveedor_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'proveedor_cliente_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'proveedor_cliente_id');
    }

    // ── Scopes ──────────────────────────────────────────────────
    public function scopeActivos($q)      { return $q->where('activo', true); }
    public function scopeProveedores($q)  { return $q->where('es_proveedor', true); }
    public function scopeClientes($q)     { return $q->where('es_cliente', true); }

    public function scopeBuscar($q, string $term)
    {
        return $q->where(function ($sub) use ($term) {
            $sub->where('razon_social', 'like', "%$term%")
                ->orWhere('nit', 'like', "%$term%")
                ->orWhere('ci', 'like', "%$term%")
                ->orWhere('nombre_comercial', 'like', "%$term%");
        });
    }

    // ── Helpers ─────────────────────────────────────────────────
    /** Identificador fiscal para SIAT: NIT o CI+complemento */
    public function identificadorFiscal(): string
    {
        if ($this->nit) return $this->nit;
        return $this->ci . ($this->complemento ? $this->complemento : '');
    }

    /** Nombre para mostrar en facturas */
    public function nombreFactura(): string
    {
        return $this->razon_social;
    }

    public function tieneCredito(): bool
    {
        return $this->dias_credito > 0 && $this->limite_credito > 0;
    }
    // app/Models/ProveedorCliente.php
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->codigo)) {
                $prefijo = $model->es_proveedor ? 'PRV' : 'CLI';
                $ultimo = static::where('empresa_id', $model->empresa_id)
                    ->where('codigo', 'like', $prefijo.'-%')
                    ->orderByDesc('id')
                    ->value('codigo');

                $numero = $ultimo
                    ? ((int) substr($ultimo, -4)) + 1
                    : 1;

                $model->codigo = $prefijo.'-'.str_pad($numero, 4, '0', STR_PAD_LEFT);
            }
        });
    }
    // app/Models/ProveedorCliente.php
    public function movimientosCtaCte(): HasMany
    {
        return $this->hasMany(MovimientoCuentaCorriente::class, 'proveedor_cliente_id')
                    ->orderBy('fecha')->orderBy('id');
    }

    /** Saldo actual: positivo = nos debe, negativo = le debemos */
    public function saldoActual(): float
    {
        $cargos = $this->movimientosCtaCte()
            ->where('estado', '!=', 'anulado')
            ->where('tipo', 'cargo')
            ->sum('monto');
        $abonos = $this->movimientosCtaCte()
            ->where('estado', '!=', 'anulado')
            ->where('tipo', 'abono')
            ->sum('monto');
        return (float) ($cargos - $abonos);
    }

    /** Saldo solo de cuentas por cobrar (clientes) */
    public function saldoPorCobrar(): float
    {
        if (!$this->es_cliente) return 0;
        return $this->saldoActual();
    }

    /** Saldo solo de cuentas por pagar (proveedores) */
    public function saldoPorPagar(): float
    {
        if (!$this->es_proveedor) return 0;
        return abs($this->saldoActual());
    }
    public static function siguienteCodigo($empresaId): string
    {
        $ultimo = self::where('empresa_id', $empresaId)
            ->where('codigo', 'like', 'PRV-%')
            ->lockForUpdate()
            ->orderByRaw('CAST(SUBSTRING(codigo, 5) AS UNSIGNED) DESC')
            ->value('codigo');

        $numero = $ultimo
            ? ((int) substr($ultimo, 4)) + 1
            : 1;

        return 'PRV-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }
}
