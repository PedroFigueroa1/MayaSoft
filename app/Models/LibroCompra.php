<?php
// ============================================================
// App\Models\LibroCompra.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibroCompra extends Model
{
    protected $table = 'libro_compras';

    protected $fillable = [
        'periodo_id', 'compra_id', 'importacion_id',
        'mes', 'especificacion', 'nit_proveedor', 'razon_social',
        'cod_autorizacion', 'nro_factura', 'nro_dui_dim', 'fecha_factura',
        'importe_total', 'importe_ice', 'importe_iehd', 'importe_ipj',
        'tasas', 'otro_no_sujeto', 'importes_exentos', 'compras_tasa_cero',
        'subtotal', 'descuento', 'importe_gift_card', 'importe_base_cf',
        'credito_fiscal', 'cf_iva_contable', 'tipo_compra', 'cod_control',
        'concepto', 'dias_credito',
        // Cuentas asignadas
        'cuenta_gasto_id', 'cuenta_iva_cf_id', 'cuenta_descuento_id',
        'cuenta_iva_descuento_id', 'cuenta_pago_id',
        // Montos calculados
        'importe_debe', 'importe_haber',
        // Control
        'asiento_id', 'estado',
        'importado_por', 'contabilizado_por', 'contabilizado_at',
        // Inventario
        'va_a_inventario', 'almacen_id', 'estado_inventario',
        'ingresado_inventario_at', 'ingresado_inventario_por',
    ];

    protected $casts = [
        'fecha_factura'           => 'date',
        'contabilizado_at'        => 'datetime',
        'ingresado_inventario_at' => 'datetime',
        'va_a_inventario'         => 'boolean',
        'importe_total'           => 'decimal:4',
        'credito_fiscal'          => 'decimal:4',
        'cf_iva_contable'         => 'decimal:4',
        'descuento'               => 'decimal:4',
        'importe_debe'            => 'decimal:4',
        'importe_haber'           => 'decimal:4',
    ];

    // ── Relaciones ──────────────────────────────────────────

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function importacion(): BelongsTo
    {
        return $this->belongsTo(Importacion::class);
    }

    public function cuentaGasto(): BelongsTo
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_gasto_id');
    }

    public function cuenta_gasto()
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_gasto_id');
    }

    public function cuenta_pago()
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_pago_id');
    }

    public function cuentaIvaCf(): BelongsTo
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_iva_cf_id');
    }

    public function cuentaDescuento(): BelongsTo
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_descuento_id');
    }

    public function cuentaIvaDescuento(): BelongsTo
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_iva_descuento_id');
    }

    public function cuentaPago(): BelongsTo
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_pago_id');
    }

    public function asiento(): BelongsTo
    {
        return $this->belongsTo(Asiento::class);
    }

    public function importadoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'importado_por');
    }

    public function contabilizadoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'contabilizado_por');
    }

    // ── Relaciones de Inventario ────────────────────────────

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function ingresadoInventarioPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'ingresado_inventario_por');
    }

    // ── Helpers generales ───────────────────────────────────

    public function tieneDescuento(): bool
    {
        return $this->descuento > 0;
    }

    public function tieneIvaCf(): bool
    {
        return $this->cf_iva_contable > 0;
    }

    // ── Helpers de Inventario ───────────────────────────────

    /** ¿La compra debe ingresar a inventario? */
    public function vaAInventario(): bool
    {
        return (bool) $this->va_a_inventario;
    }

    /** ¿Ya fue ingresada al inventario? */
    public function estaIngresadoAInventario(): bool
    {
        return $this->estado_inventario === 'ingresado';
    }

    /** ¿Está pendiente de ingresar al inventario? */
    public function estaPendienteInventario(): bool
    {
        return $this->estado_inventario === 'pendiente';
    }

    /** ¿No aplica inventario? */
    public function noAplicaInventario(): bool
    {
        return $this->estado_inventario === 'no_aplica';
    }

    /**
     * Marca la compra como pendiente de ingreso a inventario.
     * Requiere que tenga almacén asignado.
     */
    public function marcarPendienteInventario(): bool
    {
        if (!$this->almacen_id) {
            return false;
        }

        $this->update([
            'va_a_inventario'   => true,
            'estado_inventario' => 'pendiente',
        ]);

        return true;
    }

    /**
     * Marca la compra como ingresada al inventario.
     * Registra usuario y fecha.
     */
    public function marcarIngresadoInventario(?int $userId = null): bool
    {
        if (!$this->vaAInventario() || !$this->almacen_id) {
            return false;
        }

        $this->update([
            'estado_inventario'        => 'ingresado',
            'ingresado_inventario_at'  => now(),
            'ingresado_inventario_por' => $userId ?? auth()->id(),
        ]);

        return true;
    }

    /**
     * Revierte el ingreso a inventario (por ejemplo, para corregir).
     */
    public function revertirIngresoInventario(): bool
    {
        if (!$this->estaIngresadoAInventario()) {
            return false;
        }

        $this->update([
            'estado_inventario'        => 'pendiente',
            'ingresado_inventario_at'  => null,
            'ingresado_inventario_por' => null,
        ]);

        return true;
    }

    // ── Scopes útiles ───────────────────────────────────────

    public function scopePendientesInventario($query)
    {
        return $query->where('estado_inventario', 'pendiente')
                     ->where('va_a_inventario', true);
    }

    public function scopeIngresadosInventario($query)
    {
        return $query->where('estado_inventario', 'ingresado');
    }

    public function scopeDelPeriodo($query, int $periodoId)
    {
        return $query->where('periodo_id', $periodoId);
    }

    // ── Generación de asiento (sin cambios) ─────────────────

    public function generarLineasAsiento(): array
    {
        $lineas = [];
        $glosaBase = $this->concepto . ' - ' . $this->razon_social . ' Fact. ' . $this->nro_factura;

        if ($this->cuenta_gasto_id) {
            $lineas[] = [
                'cuenta_id'   => $this->cuenta_gasto_id,
                'debe'        => round($this->importe_total - $this->cf_iva_contable - $this->descuento, 4),
                'haber'       => 0,
                'descripcion' => $glosaBase,
                'deducible'   => false,
            ];
        }

        if ($this->tieneIvaCf() && $this->cuenta_iva_cf_id) {
            $lineas[] = [
                'cuenta_id'   => $this->cuenta_iva_cf_id,
                'debe'        => round($this->cf_iva_contable, 4),
                'haber'       => 0,
                'descripcion' => 'CF IVA - ' . $this->nro_factura,
                'deducible'   => true,
            ];
        }

        if ($this->tieneDescuento()) {
            if ($this->cuenta_iva_descuento_id) {
                $ivaDescuento = round($this->descuento * 0.13, 4);
                $lineas[] = [
                    'cuenta_id'   => $this->cuenta_iva_descuento_id,
                    'debe'        => $ivaDescuento,
                    'haber'       => 0,
                    'descripcion' => 'IVA DF descuento - Fact. ' . $this->nro_factura,
                    'deducible'   => false,
                ];
            }

            if ($this->cuenta_descuento_id) {
                $lineas[] = [
                    'cuenta_id'   => $this->cuenta_descuento_id,
                    'debe'        => 0,
                    'haber'       => round($this->descuento, 4),
                    'descripcion' => 'Descuento - Fact. ' . $this->nro_factura,
                    'deducible'   => false,
                ];
            }
        }

        if ($this->cuenta_pago_id) {
            $lineas[] = [
                'cuenta_id'   => $this->cuenta_pago_id,
                'debe'        => 0,
                'haber'       => round($this->importe_total, 4),
                'descripcion' => 'Pago - ' . $this->razon_social . ' Fact. ' . $this->nro_factura,
                'deducible'   => false,
            ];
        }

        return $lineas;
    }

    public function estaListaParaContabilizar(): bool
    {
        if (!$this->cuenta_gasto_id || !$this->cuenta_pago_id) return false;
        if ($this->tieneIvaCf() && !$this->cuenta_iva_cf_id) return false;
        if ($this->tieneDescuento() && !$this->cuenta_descuento_id) return false;
        return true;
    }
}
