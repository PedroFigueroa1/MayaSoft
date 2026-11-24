<?php

// app/Models/ExchangeRate.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = ['type', 'value', 'buy_value', 'date', 'consulted_at'];
    protected $casts = [
    'fecha_actualizacion' => 'datetime',
];

    // Ejemplos de helpers para tu sistema contable
    public static function ufv($date = null)
    {
        $date = $date ?: now()->toDateString();
        return static::where('type', 'ufv')->whereDate('date', $date)->first()?->value;
    }

    public static function dolarOficialVenta($date = null)
    {
        $date = $date ?: now()->toDateString();
        return static::where('type', 'dolar_oficial')->whereDate('date', $date)->first()?->value;
    }

    public static function dolarParaleloCompra($date = null)
    {
        $date = $date ?: now()->toDateString();
        return static::where('type', 'dolar_paralelo')->whereDate('date', $date)->first()?->buy_value;
    }
}
