<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoVenta extends Model
{
    /**
     * Nombre de la tabla en la BD.
     * Laravel pluralizaría "PagoVenta" → "pago_ventas" o "pagos_ventas"
     * según la versión; ser explícito evita ambigüedades.
     */
    protected $table = 'pagos_ventas';

    protected $fillable = [
        'venta_id',
        'medio',
        'monto',
        'cambio',
        'fecha',
        'referencia',
        'cuenta_id',
    ];

    protected $casts = [
        'fecha'  => 'date',
        'monto'  => 'decimal:4',
        'cambio' => 'decimal:4',
    ];

    // ── Relaciones ──────────────────────────────────────────
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_id');
    }

    // ── Helpers ─────────────────────────────────────────────
    public function esEfectivo(): bool
    {
        return $this->medio === 'efectivo';
    }

    public function esCredito(): bool
    {
        return $this->medio === 'credito';
    }

    public function esElectronico(): bool
    {
        return in_array($this->medio, ['tarjeta_debito', 'tarjeta_credito', 'qr', 'transferencia']);
    }

    /** Etiqueta legible del medio de pago */
    public function etiquetaMedio(): string
    {
        return match ($this->medio) {
            'efectivo'        => 'Efectivo',
            'tarjeta_debito'  => 'Tarjeta de débito',
            'tarjeta_credito' => 'Tarjeta de crédito',
            'qr'              => 'QR',
            'transferencia'   => 'Transferencia',
            'cheque'          => 'Cheque',
            'credito'         => 'A crédito (CxC)',
            default           => ucfirst($this->medio),
        };
    }
}
