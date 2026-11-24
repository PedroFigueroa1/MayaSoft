<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Producto extends Model
{
    use SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'empresa_id',
        'codigo', 'codigo_barra', 'descripcion', 'descripcion_larga',
        'categoria_id', 'unidad_medida_id', 'tipo',
        'precio_costo', 'precio_venta', 'precio_venta_2', 'precio_venta_3',
        'aplica_iva', 'porcentaje_iva', 'metodo_costeo',
        'cuenta_activo_id', 'cuenta_costo_venta_id',
        'cuenta_ingreso_id', 'cuenta_variacion_id',
        'stock_minimo', 'stock_maximo', 'controla_stock',
        'codigo_sin', 'descripcion_sin',
        'activo', 'creado_por',
    ];

    protected $casts = [
        'aplica_iva'     => 'boolean',
        'controla_stock' => 'boolean',
        'activo'         => 'boolean',
        'precio_costo'   => 'decimal:4',
        'precio_venta'   => 'decimal:4',
        'precio_venta_2' => 'decimal:4',
        'precio_venta_3' => 'decimal:4',
        'porcentaje_iva' => 'decimal:2',
        'stock_minimo'   => 'decimal:4',
        'stock_maximo'   => 'decimal:4',
    ];

    // ── Constantes ──────────────────────────────────────────────
    const METODO_PROMEDIO = 'PROM';
    const METODO_PEPS     = 'PEPS';
    const METODO_ESPECIFICO = 'ESP';

    const TIPO_MERCADERIA        = 'mercaderia';
    const TIPO_MATERIA_PRIMA     = 'materia_prima';
    const TIPO_PROD_TERMINADO    = 'producto_terminado';
    const TIPO_SERVICIO          = 'servicio';
    const TIPO_ACTIVO_FIJO       = 'activo_fijo';

    // ── Relaciones ──────────────────────────────────────────────
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }
    public function stockActualAlmacen(): HasOne
    {
        return $this->hasOne(StockActual::class, 'producto_id');
    }

    public function cuentaActivo(): BelongsTo
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_activo_id');
    }

    public function cuentaCostoVenta(): BelongsTo
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_costo_venta_id');
    }

    public function cuentaIngreso(): BelongsTo
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_ingreso_id');
    }

    public function cuentaVariacion(): BelongsTo
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_variacion_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'creado_por');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'producto_id');
    }

    public function lotesPeps(): HasMany
    {
        return $this->hasMany(LotePeps::class, 'producto_id')
                    ->where('agotado', false)
                    ->orderBy('fecha_entrada');
    }

    public function stockActual(): HasMany
    {
        return $this->hasMany(StockActual::class, 'producto_id');
    }

    public function detalleVentas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'producto_id');
    }

    public function detalleCompras(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'producto_id');
    }

    // ── Scopes ──────────────────────────────────────────────────
    public function scopeActivos($q)       { return $q->where('activo', true); }
    public function scopeConStock($q)      { return $q->where('controla_stock', true); }
    public function scopeServicios($q)     { return $q->where('tipo', self::TIPO_SERVICIO); }
    public function scopeMercaderia($q)    { return $q->where('tipo', self::TIPO_MERCADERIA); }

    public function scopeBuscar($q, string $term)
    {
        return $q->where(function ($sub) use ($term) {
            $sub->where('descripcion', 'like', "%$term%")
                ->orWhere('codigo', 'like', "%$term%")
                ->orWhere('codigo_barra', 'like', "%$term%");
        });
    }

    // ── Helpers de negocio ──────────────────────────────────────

    /** Stock disponible en un almacén específico */
    public function stockEnAlmacen(int $almacenId): float
    {
        $registro = StockActual::where('producto_id', $this->id)
                               ->where('almacen_id', $almacenId)
                               ->first();
        return $registro ? (float) $registro->cantidad : 0.0;
    }

    /** Stock total en todos los almacenes */
    public function stockTotal(): float
    {
        return (float) StockActual::where('producto_id', $this->id)->sum('cantidad');
    }

    /** Costo promedio vigente en un almacén */
    public function costoPromedioEnAlmacen(int $almacenId): float
    {
        $registro = StockActual::where('producto_id', $this->id)
                               ->where('almacen_id', $almacenId)
                               ->first();
        return $registro ? (float) $registro->costo_promedio : (float) $this->precio_costo;
    }

    /** ¿Hay suficiente stock para vender? */
    public function hayStock(float $cantidad, int $almacenId): bool
    {
        if (!$this->controla_stock) return true;
        return $this->stockEnAlmacen($almacenId) >= $cantidad;
    }

    /** ¿Está bajo el mínimo? */
    public function bajoBajominimo(int $almacenId): bool
    {
        if ($this->stock_minimo <= 0) return false;
        return $this->stockEnAlmacen($almacenId) < $this->stock_minimo;
    }

    /** Precio con IVA incluido */
    public function precioConIva(float $precio = null): float
    {
        $base = $precio ?? (float) $this->precio_venta;
        if (!$this->aplica_iva) return $base;
        return round($base * (1 + $this->porcentaje_iva / 100), 2);
    }

    /** IVA de un importe (el precio ya incluye IVA en Bolivia) */
    public function calcularIva(float $importe): float
    {
        if (!$this->aplica_iva) return 0.0;
        // En Bolivia el IVA está incluido: IVA = importe × 13/113
        return round($importe * ($this->porcentaje_iva / (100 + $this->porcentaje_iva)), 4);
    }

    /** Verifica que tenga las 3 cuentas contables mínimas */
    public function tieneCuentasContables(): bool
    {
        return $this->cuenta_activo_id
            && $this->cuenta_costo_venta_id
            && $this->cuenta_ingreso_id;
    }

    public function usaPeps():     bool { return $this->metodo_costeo === self::METODO_PEPS; }
    public function usaPromedio(): bool { return $this->metodo_costeo === self::METODO_PROMEDIO; }
    public function esServicio():  bool { return $this->tipo === self::TIPO_SERVICIO; }
        protected static function booted(): void
    {
        static::addGlobalScope('empresa', function ($query) {
            if (auth()->check() && auth()->user()->empresa_id) {
                $query->where('empresa_id', auth()->user()->empresa_id);
            }
        });

        static::creating(function ($model) {
            if (auth()->check()) {
                if (auth()->user()->empresa_id) {
                    $model->empresa_id = auth()->user()->empresa_id;
                }
                $model->creado_por = auth()->id();
            }
        });
    }


    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function getTipoTextoAttribute(): string
    {
        return match($this->tipo) {
            'mercaderia' => 'Mercadería',
            'materia_prima' => 'Materia Prima',
            'producto_terminado' => 'Producto Terminado',
            'servicio' => 'Servicio',
            'activo_fijo' => 'Activo Fijo',
            default => $this->tipo,
        };
    }

    public function getMetodoCosteoTextoAttribute(): string
    {
        return match($this->metodo_costeo) {
            'PROM' => 'Promedio Ponderado',
            'PEPS' => 'PEPS',
            'ESP' => 'Específico',
            default => $this->metodo_costeo,
        };
    }
}
