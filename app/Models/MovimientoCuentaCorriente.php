<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoCuentaCorriente extends Model
{
    use SoftDeletes;

    protected $table = 'movimientos_cuenta_corriente';

    protected $fillable = [
        'empresa_id', 'proveedor_cliente_id', 'fecha', 'fecha_vencimiento',
        'tipo', 'concepto', 'documento_tipo', 'documento_numero',
        'monto', 'monto_pendiente', 'monto_aplicado', 'saldo', 'moneda',
        'estado', 'referencia_id', 'referencia_tipo', 'creado_por',
    ];

    protected $casts = [
        'fecha'             => 'date',
        'fecha_vencimiento' => 'date',
        'monto'             => 'decimal:2',
        'monto_pendiente'   => 'decimal:2',
        'monto_aplicado'    => 'decimal:2',
        'saldo'             => 'decimal:2',
    ];

    // ── Relaciones ──────────────────────────────────────────

    public function proveedorCliente(): BelongsTo
    {
        return $this->belongsTo(ProveedorCliente::class, 'proveedor_cliente_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // ── Scopes ──────────────────────────────────────────────

    /** Solo movimientos vigentes (no anulados) */
    public function scopeVigentes($q)
    {
        return $q->where('estado', '!=', 'anulado');
    }

    /** Cargos con saldo pendiente (para aging y aplicación FIFO) */
    public function scopePendientes($q)
    {
        return $q->where('tipo', 'cargo')
                 ->whereIn('estado', ['pendiente', 'parcial'])
                 ->where('monto_pendiente', '>', 0);
    }

    /** Abonos con saldo a favor sin aplicar */
    public function scopeConSaldoAFavor($q)
    {
        return $q->where('tipo', 'abono')
                 ->where('monto_pendiente', '>', 0);
    }

    // ── Helpers ─────────────────────────────────────────────

    public function esCargo(): bool   { return $this->tipo === 'cargo'; }
    public function esAbono(): bool   { return $this->tipo === 'abono'; }
    public function estaAnulado(): bool { return $this->estado === 'anulado'; }
    public function estaPagado(): bool  { return $this->estado === 'pagado'; }
    public function estaPendiente(): bool { return in_array($this->estado, ['pendiente', 'parcial']); }

    // ── Boot ────────────────────────────────────────────────

    protected static function booted()
    {
        static::creating(function ($m) {
            if (auth()->check()) {
                $m->empresa_id = $m->empresa_id ?: auth()->user()->empresa_id;
                $m->creado_por = $m->creado_por ?: auth()->id();
            }

            // Inicializar monto_pendiente según tipo si no viene seteado
            if ($m->monto_pendiente === null) {
                $m->monto_pendiente = $m->tipo === 'cargo'
                    ? (float) $m->monto
                    : 0;
            }
        });
    }
}
