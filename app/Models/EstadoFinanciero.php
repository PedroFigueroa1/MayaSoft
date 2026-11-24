<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoFinanciero extends Model
{
    use HasFactory;

    protected $table = 'estados_financieros';

    protected $fillable = [
        'periodo_id',
        'tipo',
        'fecha_generacion',
        'generado_por',
        'archivo_pdf',
        'observaciones',
    ];

    protected $casts = [
        'fecha_generacion' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }

    public function generadoPor()
    {
        return $this->belongsTo(User::class, 'generado_por');
    }
}
