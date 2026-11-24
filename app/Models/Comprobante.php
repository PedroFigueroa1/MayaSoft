<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comprobante extends Model
{
    protected $fillable = [
        'periodo_id', 'tipo', 'numero', 'fecha_emision', 'fecha_limite_emision',
        'lugar_emision', 'numero_autorizacion', 'codigo_control', 'qr',
        'emisor_id', 'receptor_id', 'subtotal', 'descuento', 'importe_base',
        'importe_ice', 'importe_exento', 'importe_iva', 'importe_it', 'total',
        'leyenda', 'archivo_pdf', 'archivo_xml', 'estado_sin', 'creado_por'
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_limite_emision' => 'date',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'importe_base' => 'decimal:2',
        'importe_ice' => 'decimal:2',
        'importe_exento' => 'decimal:2',
        'importe_iva' => 'decimal:2',
        'importe_it' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public const TIPOS = [
        'FAC' => 'Factura',
        'FAC_E' => 'Factura Electrónica',
        'REC' => 'Recibo',
        'ND' => 'Nota de Débito',
        'NC' => 'Nota de Crédito',
        'LQD' => 'Liquidación',
        'CON' => 'Contrato',
        'ORD' => 'Orden',
        'PAG' => 'Pago',
        'AJT' => 'Ajuste'
    ];

    public const ESTADOS_SIN = [
        'NO_ENVIADO' => 'No Enviado',
        'PENDIENTE' => 'Pendiente',
        'VALIDADO' => 'Validado',
        'ANULADO' => 'Anulado',
        'ERROR' => 'Error'
    ];

    // Relaciones
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function asiento(): HasOne
    {
        return $this->hasOne(Asiento::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleComprobante::class);
    }

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'emisor_id');
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(Tercero::class, 'receptor_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
