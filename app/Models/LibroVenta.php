<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibroVenta extends Model
{
    protected $table = 'libro_ventas';

    protected $fillable = [
        'periodo_id', 'importacion_id', 'nro_correlativo', 'especificacion',
        'fecha_factura', 'nro_factura', 'cod_autorizacion', 'nit_cliente', 'complemento',
        'razon_social_cliente', 'importe_total', 'importe_ice', 'importe_iehd',
        'importe_ipj', 'tasas', 'otros_no_sujetos', 'exportaciones_exentas',
        'ventas_tasa_cero', 'subtotal', 'descuento', 'importe_gift_card',
        'importe_base_df', 'debito_fiscal', 'estado_factura', 'cod_control',
        'tipo_venta', 'importe_debe', 'importe_haber', 'estado', 'importado_por',

        // Cuentas
        'cuenta_ingreso_id', 'cuenta_iva_df_id', 'cuenta_descuento_id',
        'cuenta_iva_descuento_id', 'cuenta_cobro_id',

        // Control contable
        'asiento_id', 'contabilizado_por', 'contabilizado_at',

        // 🔥 Inventario
        'venta_id', 'va_a_inventario', 'almacen_id', 'estado_inventario',
        'ingresado_inventario_at', 'ingresado_inventario_por',
    ];

    protected $casts = [
        'fecha_factura'           => 'date',
        'contabilizado_at'        => 'datetime',
        'ingresado_inventario_at' => 'datetime',
        'va_a_inventario'         => 'boolean',
        'importe_total'           => 'decimal:4',
        'debito_fiscal'           => 'decimal:4',
        'descuento'               => 'decimal:4',
        'importe_debe'            => 'decimal:4',
        'importe_haber'           => 'decimal:4',
    ];

    // ── Relaciones ────────────────────────────────────────────

    public function periodo(): BelongsTo        { return $this->belongsTo(Periodo::class); }
    public function importacion(): BelongsTo     { return $this->belongsTo(Importacion::class); }
    public function cuentaIngreso(): BelongsTo   { return $this->belongsTo(Cuentas::class, 'cuenta_ingreso_id'); }
    public function cuentaIvaDf(): BelongsTo     { return $this->belongsTo(Cuentas::class, 'cuenta_iva_df_id'); }
    public function cuentaDescuento(): BelongsTo { return $this->belongsTo(Cuentas::class, 'cuenta_descuento_id'); }
    public function cuentaIvaDescuento(): BelongsTo { return $this->belongsTo(Cuentas::class, 'cuenta_iva_descuento_id'); }
    public function cuentaCobro(): BelongsTo     { return $this->belongsTo(Cuentas::class, 'cuenta_cobro_id'); }
    public function asiento(): BelongsTo         { return $this->belongsTo(Asiento::class); }
    public function importadoPor(): BelongsTo    { return $this->belongsTo(User::class, 'importado_por'); }
    public function contabilizadoPor(): BelongsTo { return $this->belongsTo(User::class, 'contabilizado_por'); }

    // 🔥 Inventario
    public function venta(): BelongsTo           { return $this->belongsTo(Venta::class, 'venta_id'); }
    public function almacen(): BelongsTo         { return $this->belongsTo(Almacen::class, 'almacen_id'); }
    public function ingresadoInventarioPor(): BelongsTo { return $this->belongsTo(User::class, 'ingresado_inventario_por'); }

    // ── Helpers generales ─────────────────────────────────────

    public function tieneDescuento(): bool { return $this->descuento > 0; }
    public function tieneIvaDf(): bool     { return $this->debito_fiscal > 0; }

    // ── Helpers de inventario ─────────────────────────────────

    public function vaAInventario(): bool
    {
        return (bool) $this->va_a_inventario;
    }

    public function estaIngresadoAInventario(): bool
    {
        return $this->estado_inventario === 'ingresado'
            || ($this->va_a_inventario === true && $this->venta_id !== null);
    }

    public function estaPendienteInventario(): bool
    {
        return $this->estado_inventario === 'pendiente';
    }

    public function noAplicaInventario(): bool
    {
        return $this->estado_inventario === 'no_aplica';
    }

    public function marcarPendienteInventario(): bool
    {
        if (!$this->almacen_id) return false;
        $this->update([
            'va_a_inventario'   => true,
            'estado_inventario' => 'pendiente',
        ]);
        return true;
    }

    public function marcarIngresadoInventario(?int $userId = null): bool
    {
        if (!$this->vaAInventario() || !$this->almacen_id) return false;
        $this->update([
            'estado_inventario'        => 'ingresado',
            'ingresado_inventario_at'  => now(),
            'ingresado_inventario_por' => $userId ?? auth()->id(),
        ]);
        return true;
    }

    public function revertirIngresoInventario(): bool
    {
        if (!$this->estaIngresadoAInventario()) return false;
        $this->update([
            'estado_inventario'        => 'pendiente',
            'ingresado_inventario_at'  => null,
            'ingresado_inventario_por' => null,
        ]);
        return true;
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopePendientesInventario($q)
    {
        return $q->where('estado_inventario', 'pendiente')->where('va_a_inventario', true);
    }

    public function scopeIngresadosInventario($q)
    {
        return $q->where('estado_inventario', 'ingresado');
    }

    public function scopeDelPeriodo($q, int $periodoId)
    {
        return $q->where('periodo_id', $periodoId);
    }

    public function scopePendientes($q)
    {
        return $q->where('estado', 'pendiente');
    }

    public function scopeContabilizados($q)
    {
        return $q->where('estado', 'contabilizado');
    }

    // ── Generación de asiento ─────────────────────────────────

    public function generarLineasAsiento(): array
    {
        $lineas = [];
        $glosa = 'Venta Fact. ' . $this->nro_factura . ' - ' . $this->razon_social_cliente;

        // CAJA o CxC (DEBE) = importe total
        if ($this->cuenta_cobro_id) {
            $lineas[] = [
                'cuenta_id'   => $this->cuenta_cobro_id,
                'debe'        => round($this->importe_total, 4),
                'haber'       => 0,
                'descripcion' => $glosa,
                'deducible'   => false,
            ];
        }

        // Descuento en ventas (DEBE) si tiene descuento
        if ($this->tieneDescuento() && $this->cuenta_descuento_id) {
            $lineas[] = [
                'cuenta_id'   => $this->cuenta_descuento_id,
                'debe'        => round($this->descuento, 4),
                'haber'       => 0,
                'descripcion' => 'Descuento - Fact. ' . $this->nro_factura,
                'deducible'   => false,
            ];
        }

        // Ingreso (HABER) = importe_total - debito_fiscal
        if ($this->cuenta_ingreso_id) {
            $lineas[] = [
                'cuenta_id'   => $this->cuenta_ingreso_id,
                'debe'        => 0,
                'haber'       => round($this->importe_total - $this->debito_fiscal, 4),
                'descripcion' => 'Ingreso - Fact. ' . $this->nro_factura,
                'deducible'   => false,
            ];
        }

        // IVA Débito Fiscal (HABER)
        if ($this->tieneIvaDf() && $this->cuenta_iva_df_id) {
            $lineas[] = [
                'cuenta_id'   => $this->cuenta_iva_df_id,
                'debe'        => 0,
                'haber'       => round($this->debito_fiscal, 4),
                'descripcion' => 'DF IVA - Fact. ' . $this->nro_factura,
                'deducible'   => false,
            ];
        }

        return $lineas;
    }

    public function estaListaParaContabilizar(): bool
    {
        if (!$this->cuenta_ingreso_id || !$this->cuenta_cobro_id) return false;
        if ($this->tieneIvaDf() && !$this->cuenta_iva_df_id) return false;
        if ($this->tieneDescuento() && !$this->cuenta_descuento_id) return false;
        return true;
    }
}
