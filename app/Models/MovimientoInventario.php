<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'tipo', 'producto_id', 'almacen_id', 'almacen_destino_id',
        'cantidad', 'costo_unitario', 'costo_total', 'precio_venta',
        'stock_resultante', 'lote_id',
        'referencia_tipo', 'referencia_id',
        'asiento_id', 'periodo_id',
        'fecha', 'motivo', 'observacion',
        'proveedor_cliente_id',
        'estado', 'motivo_anulacion', 'anulado_por', 'anulado_at',
        'creado_por',
    ];

    protected $casts = [
        'cantidad'         => 'decimal:4',
        'costo_unitario'   => 'decimal:4',
        'costo_total'      => 'decimal:4',
        'precio_venta'     => 'decimal:4',
        'stock_resultante' => 'decimal:4',
        'fecha'            => 'date',
        'anulado_at'       => 'datetime',
    ];

    // Tipos que suman al stock
    const ENTRADAS = [
        'entrada_compra', 'entrada_devolucion', 'entrada_produccion',
        'entrada_manual', 'entrada_traspaso',
        'ajuste_positivo',
    ];

    // Tipos que restan del stock
    const SALIDAS = [
        'salida_venta', 'salida_consumo', 'salida_merma',
        'salida_devolucion', 'salida_produccion', 'salida_traspaso',
        'ajuste_negativo',
    ];

    // ── Relaciones ──────────────────────────────────────────────
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(LotePeps::class, 'lote_id');
    }

    public function asiento(): BelongsTo
    {
        return $this->belongsTo(Asiento::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function proveedorCliente(): BelongsTo
    {
        return $this->belongsTo(ProveedorCliente::class, 'proveedor_cliente_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'creado_por');
    }

    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'anulado_por');
    }

    /** Relación polimórfica: puede apuntar a Compra, Venta, AjusteInventario, etc. */
    public function referencia(): MorphTo
    {
        return $this->morphTo('referencia');
    }

    // ── Scopes ──────────────────────────────────────────────────
    public function scopeActivos($q)   { return $q->where('estado', 'activo'); }
    public function scopeEntradas($q)  { return $q->whereIn('tipo', self::ENTRADAS); }
    public function scopeSalidas($q)   { return $q->whereIn('tipo', self::SALIDAS); }

    public function scopeDeProducto($q, int $productoId)
    {
        return $q->where('producto_id', $productoId);
    }

    public function scopeDeAlmacen($q, int $almacenId)
    {
        return $q->where('almacen_id', $almacenId);
    }

    public function scopeEnPeriodo($q, int $periodoId)
    {
        return $q->where('periodo_id', $periodoId);
    }

    public function scopeEntreFechas($q, string $desde, string $hasta)
    {
        return $q->whereBetween('fecha', [$desde, $hasta]);
    }

    // ── Helpers ─────────────────────────────────────────────────
    public function esEntrada(): bool { return in_array($this->tipo, self::ENTRADAS); }
    public function esSalida():  bool { return in_array($this->tipo, self::SALIDAS); }
    public function estaActivo(): bool { return $this->estado === 'activo'; }

    /** Signo de la cantidad para cálculos: +1 entradas, -1 salidas */
    public function signo(): int { return $this->esEntrada() ? 1 : -1; }

    /** Utilidad bruta (solo en salidas de venta) */
    public function utilidad(): float
    {
        if ($this->tipo !== 'salida_venta') return 0.0;
        return round((float) $this->precio_venta - (float) $this->costo_total, 4);
    }

    /** Anular este movimiento (no lo borra, solo marca como anulado) */
    public function anular(string $motivo, int $usuarioId): void
    {
        $this->update([
            'estado'            => 'anulado',
            'motivo_anulacion'  => $motivo,
            'anulado_por'       => $usuarioId,
            'anulado_at'        => now(),
        ]);
    }

    /** Etiqueta legible del tipo */
    public function tipoLabel(): string
    {
        return match($this->tipo) {
            'entrada_compra'     => 'Entrada por compra',
            'entrada_devolucion' => 'Devolución de cliente',
            'entrada_produccion' => 'Entrada producción',
            'entrada_manual'     => 'Entrada manual',
            'entrada_traspaso'   => 'Entrada por traspaso',
            'salida_venta'       => 'Salida por venta',
            'salida_consumo'     => 'Consumo interno',
            'salida_merma'       => 'Merma / pérdida',
            'salida_devolucion'  => 'Devolución a proveedor',
            'salida_produccion'  => 'Salida producción',
            'salida_traspaso'    => 'Salida por traspaso',
            'ajuste_positivo'    => 'Ajuste positivo',
            'ajuste_negativo'    => 'Ajuste negativo',
            default              => $this->tipo,
        };
    }
}
