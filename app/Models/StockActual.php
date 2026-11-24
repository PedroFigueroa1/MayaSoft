<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockActual extends Model
{
    protected $table = 'stock_actual';

    protected $fillable = [
        'producto_id', 'almacen_id',
        'cantidad', 'costo_promedio', 'costo_total',
        'ultimo_movimiento',
    ];

    protected $casts = [
        'cantidad'          => 'decimal:4',
        'costo_promedio'    => 'decimal:4',
        'costo_total'       => 'decimal:4',
        'ultimo_movimiento' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    // ── Scopes ──────────────────────────────────────────────────
    public function scopeConStock($q)
    {
        return $q->where('cantidad', '>', 0);
    }

    public function scopeBajoMinimo($q)
    {
        return $q->whereHas('producto', function ($p) {
            $p->whereColumn('stock_actual.cantidad', '<', 'productos.stock_minimo')
              ->where('productos.stock_minimo', '>', 0);
        });
    }

    // ── Helpers ─────────────────────────────────────────────────

    /**
     * Recalcula el costo promedio al recibir una entrada.
     * Fórmula: (stock_actual × costo_promedio + cant_nueva × costo_nuevo)
     *          ÷ (stock_actual + cant_nueva)
     */
    public function recalcularPromedio(float $cantNueva, float $costoNuevo): float
    {
        $stockActual    = (float) $this->cantidad;
        $costoActual    = (float) $this->costo_promedio;
        $totalUnidades  = $stockActual + $cantNueva;

        if ($totalUnidades <= 0) return $costoNuevo;

        return round(
            ($stockActual * $costoActual + $cantNueva * $costoNuevo) / $totalUnidades,
            4
        );
    }

    public function aplicarEntrada(float $cantidad, float $costoUnitario): void
    {
        $nuevoCosto = $this->recalcularPromedio($cantidad, $costoUnitario);
        $nuevaCant  = round((float) $this->cantidad + $cantidad, 4);

        $this->update([
            'costo_promedio'    => $nuevoCosto,
            'cantidad'          => $nuevaCant,
            'costo_total'       => round($nuevaCant * $nuevoCosto, 4),
            'ultimo_movimiento' => now(),
        ]);
    }

    /**
     * PROM: sin $costoTotalSalida → valor = cantidad × promedio (el promedio no cambia).
     * PEPS/ESP: con $costoTotalSalida → se resta el costo real y se recalcula el promedio.
     */
    public function aplicarSalida(float $cantidad, ?float $costoTotalSalida = null): void
    {
        $actual = (float) $this->cantidad;

        if ($cantidad > $actual + 0.0001) {
            throw new \Exception("Stock insuficiente. Disponible: {$actual} | Requerido: {$cantidad}");
        }

        $nuevaCant = max(0, round($actual - $cantidad, 4));

        if ($costoTotalSalida === null) {
            $nuevoValor    = round($nuevaCant * (float) $this->costo_promedio, 4);
            $nuevoPromedio = (float) $this->costo_promedio;
        } else {
            $nuevoValor    = $nuevaCant > 0
                ? max(0, round((float) $this->costo_total - $costoTotalSalida, 4))
                : 0;
            $nuevoPromedio = $nuevaCant > 0
                ? round($nuevoValor / $nuevaCant, 4)
                : (float) $this->costo_promedio;
        }

        $this->update([
            'cantidad'          => $nuevaCant,
            'costo_total'       => $nuevoValor,
            'costo_promedio'    => $nuevoPromedio,
            'ultimo_movimiento' => now(),
        ]);
    }

    /** Anula una ENTRADA: quita cantidad y el valor exacto con que entró. */
    public function revertirEntrada(float $cantidad, float $costoTotalMovimiento): void
    {
        $actual = (float) $this->cantidad;

        if ($cantidad > $actual + 0.0001) {
            throw new \Exception('No se puede anular: parte de esta entrada ya fue consumida.');
        }

        $nuevaCant  = max(0, round($actual - $cantidad, 4));
        $nuevoValor = $nuevaCant > 0
            ? max(0, round((float) $this->costo_total - $costoTotalMovimiento, 4))
            : 0;

        $this->update([
            'cantidad'          => $nuevaCant,
            'costo_total'       => $nuevoValor,
            'costo_promedio'    => $nuevaCant > 0 ? round($nuevoValor / $nuevaCant, 4) : $this->costo_promedio,
            'ultimo_movimiento' => now(),
        ]);
    }

    /** Anula una SALIDA: devuelve cantidad al costo con que salió. */
    public function revertirSalida(float $cantidad, float $costoUnitarioOriginal): void
    {
        $this->aplicarEntrada($cantidad, $costoUnitarioOriginal);
    }

    /** Obtiene o crea la fila y la BLOQUEA. Llamar siempre dentro de DB::transaction(). */
    public static function obtenerOCrear(int $productoId, int $almacenId): self
    {
        self::firstOrCreate(
            ['producto_id' => $productoId, 'almacen_id' => $almacenId],
            ['cantidad' => 0, 'costo_promedio' => 0, 'costo_total' => 0]
        );

        return self::where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
            ->lockForUpdate()
            ->firstOrFail();
    }

}
