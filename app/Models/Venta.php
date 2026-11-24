<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    use SoftDeletes;

    protected $table = 'ventas';

    protected $fillable = [
        'empresa_id',
        'nro_venta', 'periodo_id', 'almacen_id', 'proveedor_cliente_id',
        'nit_cliente', 'ci_cliente', 'complemento_cliente', 'razon_social_cliente',
        'fecha', 'hora', 'tipo', 'venta_origen_id',
        'forma_pago', 'dias_credito', 'fecha_vencimiento_cobro',
        'subtotal', 'descuento', 'iva_df', 'total',
        'cuenta_cobro_id', 'cuenta_iva_df_id',
        'con_factura', 'modalidad_factura',
        'nro_factura', 'cod_autorizacion', 'cod_control',
        'cuf', 'cufd', 'fecha_emision_factura',
        'estado_siat', 'respuesta_siat', 'enviado_siat_at',
        'asiento_id', 'estado_contable', 'contabilizado_at', 'contabilizado_por',
        'estado', 'motivo_anulacion', 'observacion', 'creado_por',
        // 🔥 NUEVO: vínculo con Libro de Ventas
        'libro_venta_id',
    ];

    protected $casts = [
        'fecha'                  => 'date',
        'fecha_vencimiento_cobro'=> 'date',
        'fecha_emision_factura'  => 'datetime',
        'enviado_siat_at'        => 'datetime',
        'contabilizado_at'       => 'datetime',
        'con_factura'            => 'boolean',
        'subtotal'               => 'decimal:4',
        'descuento'              => 'decimal:4',
        'iva_df'                 => 'decimal:4',
        'total'                  => 'decimal:4',
    ];

    // ── Relaciones ──────────────────────────────────────────────
    public function periodo(): BelongsTo      { return $this->belongsTo(Periodo::class); }
    public function almacen(): BelongsTo      { return $this->belongsTo(Almacen::class); }
    public function cliente(): BelongsTo      { return $this->belongsTo(ProveedorCliente::class, 'proveedor_cliente_id'); }
    public function cuentaCobro(): BelongsTo  { return $this->belongsTo(Cuentas::class, 'cuenta_cobro_id'); }
    public function cuentaIvaDf(): BelongsTo  { return $this->belongsTo(Cuentas::class, 'cuenta_iva_df_id'); }
    public function asiento(): BelongsTo      { return $this->belongsTo(Asiento::class); }
    public function creadoPor(): BelongsTo    { return $this->belongsTo(User::class, 'creado_por'); }
    public function contabilizadoPor(): BelongsTo { return $this->belongsTo(User::class, 'contabilizado_por'); }
    public function ventaOrigen(): BelongsTo  { return $this->belongsTo(Venta::class, 'venta_origen_id'); }

    // 🔥 NUEVO: relación con Libro de Ventas
    public function libroVenta(): BelongsTo
    {
        return $this->belongsTo(LibroVenta::class, 'libro_venta_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoVenta::class, 'venta_id');
    }

    public function notasDebito(): HasMany
    {
        return $this->hasMany(Venta::class, 'venta_origen_id')
                    ->where('tipo', 'nota_debito');
    }

    public function notasCredito(): HasMany
    {
        return $this->hasMany(Venta::class, 'venta_origen_id')
                    ->where('tipo', 'nota_credito');
    }

    public function colaSiat(): HasMany
    {
        return $this->hasMany(ColaSiat::class, 'venta_id');
    }

    // ── Scopes ──────────────────────────────────────────────────
    public function scopeConfirmadas($q)        { return $q->where('estado', 'confirmada'); }
    public function scopePendientesContabilizar($q) { return $q->where('estado_contable', 'pendiente')->where('estado', 'confirmada'); }
    public function scopePendientesSiat($q)     { return $q->where('estado_siat', 'pendiente'); }
    public function scopeAprobadas($q)          { return $q->where('estado_siat', 'aprobada'); }
    public function scopeDeHoy($q)              { return $q->where('fecha', today()); }

    public function scopeDeAlmacen($q, int $almacenId)
    {
        return $q->where('almacen_id', $almacenId);
    }

    public function scopeEntreFechas($q, string $desde, string $hasta)
    {
        return $q->whereBetween('fecha', [$desde, $hasta]);
    }

    // 🔥 NUEVO: scopes para el flujo libro de ventas
    public function scopeDesdeLibro($q)
    {
        return $q->whereNotNull('libro_venta_id');
    }

    public function scopeManuales($q)
    {
        return $q->whereNull('libro_venta_id');
    }

    // ── Helpers de estado ────────────────────────────────────────
    public function esBorrador():        bool { return $this->estado === 'borrador'; }
    public function estaConfirmada():    bool { return $this->estado === 'confirmada'; }
    public function estaAnulada():       bool { return $this->estado === 'anulada'; }
    public function estaContabilizada(): bool { return $this->estado_contable === 'contabilizada'; }
    public function facturaAprobada():   bool { return $this->estado_siat === 'aprobada'; }
    public function facturaOffline():    bool { return $this->modalidad_factura === 'offline'; }
    public function sinFactura():        bool { return $this->modalidad_factura === 'sin_factura'; }
    public function pendienteSiat():     bool { return $this->estado_siat === 'pendiente'; }

    // 🔥 NUEVO: ¿ya está centralizada en el libro de ventas?
    public function estaEnLibro():       bool { return $this->libro_venta_id !== null; }

    /** Nombre del cliente para mostrar/imprimir */
    public function nombreCliente(): string
    {
        return $this->razon_social_cliente ?? 'SIN NOMBRE';
    }

    /** NIT o CI del cliente para SIAT */
    public function nitCliente(): string
    {
        return $this->nit_cliente ?? $this->ci_cliente ?? '0';
    }

    /** Recalcula totales desde los detalles */
    public function recalcularTotales(): void
    {
        $subtotal  = $this->detalles->sum('subtotal');
        $descuento = (float) $this->descuento;
        $base      = $subtotal - $descuento;
        $iva       = round($base * 13 / 113, 4); // IVA incluido Bolivia

        $this->update([
            'subtotal' => $base,
            'iva_df'   => $iva,
            'total'    => $subtotal - $descuento,
        ]);
    }

    /**
     * Construye el payload para enviar al SIAT.
     */
    public function buildPayloadSiat(): array
    {
        return [
            'nitEmisor'         => config('siat.nit_empresa'),
            'nitAdquiriente'    => $this->nitCliente(),
            'complemento'       => $this->complemento_cliente,
            'codigoCliente'     => $this->proveedor_cliente_id,
            'nombreRazonSocial' => $this->nombreCliente(),
            'numeroFactura'     => $this->nro_factura,
            'cuf'               => $this->cuf,
            'cufd'              => $this->cufd,
            'codigoSucursal'    => config('siat.cod_sucursal', 0),
            'codigoPuntoVenta'  => config('siat.cod_punto_venta', 0),
            'fechaHora'         => $this->fecha_emision_factura?->format('Y-m-d\TH:i:s.v'),
            'montoTotal'        => (float) $this->total,
            'montoTotalSujetoIva' => (float) $this->total - (float) $this->descuento,
            'descuentos'        => (float) $this->descuento,
            'detalles'          => $this->detalles->map(fn($d) => [
                'actividadEconomica' => $d->producto->codigo_sin,
                'descripcion'        => $d->producto->descripcion_sin ?? $d->producto->descripcion,
                'cantidad'           => (float) $d->cantidad,
                'precioUnitario'     => (float) $d->precio_unitario,
                'montoDescuento'     => (float) $d->descuento,
                'subTotal'           => (float) $d->subtotal,
            ])->toArray(),
        ];
    }

    /** Genera número siguiente: VT-0001 */
    public static function siguienteNumero(int $periodoId, int $empresaId): string
    {
        $ultimo = static::where('empresa_id', $empresaId)
                        ->where('periodo_id', $periodoId)
                        ->orderBy('id', 'desc')
                        ->lockForUpdate()
                        ->value('nro_venta');

        if (!$ultimo) return 'VT-0001';

        $n = intval(substr($ultimo, 3)) + 1;
        return 'VT-' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    public function puntoVenta(): BelongsTo
    {
        return $this->belongsTo(PuntoVenta::class, 'punto_venta_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Venta $venta) {
            if (empty($venta->empresa_id)) {
                $venta->empresa_id = auth()->user()?->empresa_id
                                ?? session('empresa_id')
                                ?? \App\Models\Empresa::value('id');
            }
        });
    }
}
