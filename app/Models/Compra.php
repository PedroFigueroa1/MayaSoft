<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    use SoftDeletes;

    protected $table = 'compras';

    protected $fillable = [
        'empresa_id', 'nro_compra', 'periodo_id', 'proveedor_id', 'almacen_id',
        'nro_factura', 'cod_autorizacion', 'fecha_factura', 'fecha_recepcion',
        'subtotal', 'descuento', 'iva_cf', 'total',
        'forma_pago', 'dias_credito', 'fecha_vencimiento_pago',
        'cuenta_pago_id', 'cuenta_iva_cf_id',
        'libro_compra_id', 'asiento_id', 'estado', 'observacion',
        'creado_por', 'confirmado_por', 'confirmado_at',
    ];

    protected $casts = [
        'fecha_factura'          => 'date',
        'fecha_recepcion'        => 'date',
        'fecha_vencimiento_pago' => 'date',
        'subtotal'               => 'decimal:4',
        'descuento'              => 'decimal:4',
        'iva_cf'                 => 'decimal:4',
        'total'                  => 'decimal:4',
        'confirmado_at'          => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────
    public function periodo(): BelongsTo   { return $this->belongsTo(Periodo::class); }
    public function proveedor(): BelongsTo { return $this->belongsTo(ProveedorCliente::class, 'proveedor_id'); }
    public function almacen(): BelongsTo   { return $this->belongsTo(Almacen::class); }
    public function cuentaPago(): BelongsTo { return $this->belongsTo(Cuentas::class, 'cuenta_pago_id'); }
    public function cuentaIvaCf(): BelongsTo { return $this->belongsTo(Cuentas::class, 'cuenta_iva_cf_id'); }
    public function libroCompra(): BelongsTo { return $this->belongsTo(LibroCompra::class, 'libro_compra_id'); }
    public function asiento(): BelongsTo   { return $this->belongsTo(Asiento::class); }
    public function creadoPor(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'creado_por'); }
    public function confirmadoPor(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'confirmado_por'); }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'compra_id');
    }

    // ── Scopes ──────────────────────────────────────────────────
    public function scopeBorradores($q)     { return $q->where('estado', 'borrador'); }
    public function scopeConfirmadas($q)    { return $q->where('estado', 'confirmada'); }
    public function scopeContabilizadas($q) { return $q->where('estado', 'contabilizada'); }

    // ── Helpers ─────────────────────────────────────────────────
    public function esBorrador():     bool { return $this->estado === 'borrador'; }
    public function estaConfirmada(): bool { return $this->estado === 'confirmada'; }
    public function tieneFactura():   bool { return !empty($this->nro_factura); }

    /** Recalcula totales desde los detalles */
    public function recalcularTotales(): void
    {
        $subtotal  = $this->detalles->sum('subtotal');
        $descuento = (float) $this->descuento;
        $base      = $subtotal - $descuento;
        $iva       = round($base * 13 / 113, 4); // IVA incluido en Bolivia

        $this->update([
            'subtotal' => $subtotal,
            'iva_cf'   => $iva,
            'total'    => $subtotal,
        ]);
    }

    /** Genera número siguiente: CO-0001 */
    public static function siguienteNumero(int $periodoId): string
    {
        $ultimo = static::where('periodo_id', $periodoId)
                        ->orderBy('id', 'desc')
                        ->value('nro_compra');
        if (!$ultimo) return 'CO-0001';
        $n = intval(substr($ultimo, 3)) + 1;
        return 'CO-' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }
}
