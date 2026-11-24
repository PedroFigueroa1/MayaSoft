<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// ============================================================
// ConfiguracionSiat — credenciales y CUFD del día
// ============================================================
class ConfiguracionSiat extends Model
{
    protected $table = 'configuracion_siat';

    protected $fillable = [
        'nit_empresa', 'razon_social',
        'cod_ambiente', 'cod_modalidad',
        'cod_sucursal', 'cod_punto_venta',
        'token_delegado', 'token_expira_at',
        'cod_tipo_factura',
        'cufd_actual', 'cufd_control',
        'cufd_emitido_at', 'cufd_expira_at',
        'cuis', 'cuis_emitido_at', 'cuis_expira_at',
        'correlativo_actual', 'fecha_correlativo',
        'url_api', 'facturas_offline_pendientes', 'activo',
    ];

    protected $casts = [
        'token_expira_at'  => 'datetime',
        'cufd_emitido_at'  => 'datetime',
        'cufd_expira_at'   => 'datetime',
        'cuis_emitido_at'  => 'datetime',
        'cuis_expira_at'   => 'datetime',
        'fecha_correlativo'=> 'date',
        'activo'           => 'boolean',
    ];

    protected $hidden = ['token_delegado']; // no exponer en JSON

    // ── Helpers ─────────────────────────────────────────────────

    /** Obtener la configuración activa (singleton) */
    public static function activa(): ?self
    {
        return static::where('activo', true)->first();
    }

    /** ¿El CUFD sigue vigente? */
    public function cufdVigente(): bool
    {
        return $this->cufd_actual
            && $this->cufd_expira_at
            && $this->cufd_expira_at->isFuture()
            && $this->fecha_correlativo?->isToday();
    }

    /** ¿El CUIS sigue vigente? */
    public function cuisVigente(): bool
    {
        return $this->cuis && $this->cuis_expira_at?->isFuture();
    }

    /** Obtener siguiente número de factura y avanzar el correlativo */
    public function siguienteCorrelativo(): int
    {
        // Si cambió el día, reiniciar
        if (!$this->fecha_correlativo?->isToday()) {
            $this->update(['correlativo_actual' => 0, 'fecha_correlativo' => today()]);
        }
        $this->increment('correlativo_actual');
        return $this->correlativo_actual;
    }

    public function esPiloto():    bool { return $this->cod_ambiente === '2'; }
    public function esProduccion():bool { return $this->cod_ambiente === '1'; }
    public function esOnline():    bool { return $this->cod_modalidad === '1'; }
    public function esOffline():   bool { return $this->cod_modalidad === '2'; }
}

// ============================================================
// ColaSiat — facturas offline pendientes de sincronizar
// ============================================================
class ColaSiat extends Model
{
    protected $table = 'cola_siat';

    protected $fillable = [
        'venta_id', 'payload_xml', 'cuf',
        'intentos', 'ultimo_intento_at', 'ultimo_error', 'estado',
    ];

    protected $casts = [
        'ultimo_intento_at' => 'datetime',
    ];

    public function venta(): BelongsTo { return $this->belongsTo(Venta::class); }

    public function scopePendientes($q) { return $q->where('estado', 'pendiente'); }
    public function scopeConErrores($q) { return $q->where('estado', 'rechazada'); }

    /** ¿Se puede reintentar? (máx 5 intentos) */
    public function puedeReintentar(): bool
    {
        return $this->intentos < 5 && $this->estado !== 'aprobada';
    }

    public function registrarIntento(string $error = null): void
    {
        $this->update([
            'intentos'          => $this->intentos + 1,
            'ultimo_intento_at' => now(),
            'ultimo_error'      => $error,
            'estado'            => $error ? 'rechazada' : 'aprobada',
        ]);
    }
}

// ============================================================
// CierreCaja — cierre diario del POS y contabilización masiva
// ============================================================
class CierreCaja extends Model
{
    protected $table = 'cierres_caja';

    protected $fillable = [
        'almacen_id', 'periodo_id', 'fecha',
        'total_ventas', 'total_facturas',
        'total_efectivo', 'total_tarjeta', 'total_qr',
        'total_credito', 'total_general', 'total_iva_df',
        'modo_contabilizacion', 'asiento_id', 'estado',
        'cerrado_por', 'cerrado_at',
        'contabilizado_por', 'contabilizado_at',
    ];

    protected $casts = [
        'fecha'             => 'date',
        'cerrado_at'        => 'datetime',
        'contabilizado_at'  => 'datetime',
        'total_efectivo'    => 'decimal:4',
        'total_tarjeta'     => 'decimal:4',
        'total_qr'          => 'decimal:4',
        'total_credito'     => 'decimal:4',
        'total_general'     => 'decimal:4',
        'total_iva_df'      => 'decimal:4',
    ];

    public function almacen(): BelongsTo        { return $this->belongsTo(Almacen::class); }
    public function periodo(): BelongsTo        { return $this->belongsTo(Periodo::class); }
    public function asiento(): BelongsTo        { return $this->belongsTo(Asiento::class); }
    public function cerradoPor(): BelongsTo     { return $this->belongsTo(\App\Models\User::class, 'cerrado_por'); }
    public function contabilizadoPor(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'contabilizado_por'); }

    /** Ventas del día de este cierre */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'almacen_id', 'almacen_id')
                    ->where('fecha', $this->fecha)
                    ->where('estado', 'confirmada');
    }

    /** Ventas pendientes de contabilizar en este cierre */
    public function ventasPendientes(): HasMany
    {
        return $this->ventas()->where('estado_contable', 'pendiente');
    }

    public function estaAbierto():       bool { return $this->estado === 'abierto'; }
    public function estaCerrado():       bool { return $this->estado === 'cerrado'; }
    public function estaContabilizado(): bool { return $this->estado === 'contabilizado'; }

    /** Recalcula los totales del día desde las ventas */
    public function recalcularTotales(): void
    {
        $ventas = $this->ventas()->with('pagos')->get();

        $this->update([
            'total_ventas'   => $ventas->count(),
            'total_facturas' => $ventas->where('con_factura', true)->count(),
            'total_efectivo' => $ventas->flatMap->pagos->where('medio', 'efectivo')->sum('monto'),
            'total_tarjeta'  => $ventas->flatMap->pagos->whereIn('medio', ['tarjeta_debito','tarjeta_credito'])->sum('monto'),
            'total_qr'       => $ventas->flatMap->pagos->where('medio', 'qr')->sum('monto'),
            'total_credito'  => $ventas->flatMap->pagos->where('medio', 'credito')->sum('monto'),
            'total_general'  => $ventas->sum('total'),
            'total_iva_df'   => $ventas->sum('iva_df'),
        ]);
    }

    /** Obtiene o crea el cierre de hoy para un almacén */
    public static function obtenerHoy(int $almacenId, int $periodoId): self
    {
        return static::firstOrCreate(
            ['almacen_id' => $almacenId, 'fecha' => today()],
            ['periodo_id' => $periodoId, 'estado' => 'abierto', 'modo_contabilizacion' => 'diario']
        );
    }
    public function esProduccion(): bool
    {
        return (string) $this->cod_ambiente === '1';
    }

    public function getNitEmpresaAttribute(): string
    {
        return (string) $this->empresa?->nit;
    }
}
