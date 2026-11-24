<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Periodo extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'fecha_inicio',
        'fecha_cierre',
        'estado',
        'cerrado_por',
        'fecha_cierre_real'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_cierre' => 'date',
    ];

    protected $appends = [
        'fecha_inicio_formateada',
        'fecha_cierre_formateada',
    ];


    public function getFechaInicioFormateadaAttribute()
    {
        return $this->fecha_inicio
            ? $this->fecha_inicio->format('d/m/Y')
            : null;
    }

    public function getFechaCierreFormateadaAttribute()
    {
        return $this->fecha_cierre
            ? $this->fecha_cierre->format('d/m/Y')
            : null;
    }

    // Estados del periodo
    const ESTADOS = [
        'A' => 'Abierto',
        'C' => 'Cerrado'
    ];

    // Relación con empresa
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }


    // Relación con usuario que cerró el periodo
    public function cerradoPor()
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    // Verificar si está abierto
    public function estaAbierto()
    {
        return $this->estado === 'A';
    }

    // Cerrar periodo
    public function cerrar()
    {
        if (!$this->estaAbierto()) {
            return false;
        }

        // Validar que todos los asientos estén balanceados
        if ($this->asientos()->where('balanceado', false)->exists()) {
            return false;
        }

        // Generar estados financieros (implementar según tu lógica)
        // EstadoFinanciero::generarParaPeriodo($this);

        // Actualizar estado
        $this->update([
            'estado' => 'C',
            'cerrado_por' => Auth::id(),
            'fecha_cierre_real' => now(),
        ]);

        return true;
    }

    // Relación con asientos
    public function asientos()
    {
        return $this->hasMany(Asiento::class);
    }

    // Obtener el nombre del estado
    public function getEstadoNombreAttribute()
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
    public function detalleAsientos()
    {
        return $this->hasManyThrough(DetalleAsiento::class, Asiento::class);
    }


}
