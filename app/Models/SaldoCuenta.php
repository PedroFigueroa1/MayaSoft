<?php
// app/Models/SaldoCuenta.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaldoCuenta extends Model
{
    protected $table = 'saldos_cuentas';

    protected $fillable = [
        'periodo_id',
        'cuenta_id',
        'saldo_inicial_debe',
        'saldo_inicial_haber',
        'total_debe',
        'total_haber',
        'saldo_final_debe',
        'saldo_final_haber',
        'estado',
        'ultimo_calculo',
        'observaciones'
    ];

    protected $casts = [
        'saldo_inicial_debe' => 'decimal:2',
        'saldo_inicial_haber' => 'decimal:2',
        'total_debe' => 'decimal:2',
        'total_haber' => 'decimal:2',
        'saldo_final_debe' => 'decimal:2',
        'saldo_final_haber' => 'decimal:2',
        'ultimo_calculo' => 'datetime'
    ];

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuentas::class, 'cuenta_id');
    }

    /**
     * Obtener el saldo neto (debe - haber)
     */
    public function getSaldoNetoAttribute(): float
    {
        return $this->saldo_final_debe - $this->saldo_final_haber;
    }

    /**
     * Determinar la naturaleza del saldo
     */
    public function getNaturalezaAttribute(): string
    {
        if ($this->saldo_final_debe > $this->saldo_final_haber) {
            return 'DEUDORA';
        } elseif ($this->saldo_final_haber > $this->saldo_final_debe) {
            return 'ACREEDORA';
        }
        return 'CERO';
    }

    /**
     * Obtener el tipo de cuenta como texto completo
     */
    public function getTipoCuentaAttribute(): string
    {
        $tipos = [
            'A' => 'ACTIVO',
            'P' => 'PASIVO',
            'PN' => 'PATRIMONIO',
            'I' => 'INGRESO',
            'G' => 'GASTO'
        ];

        return $tipos[$this->cuenta->tipo] ?? 'DESCONOCIDO';
    }
}
