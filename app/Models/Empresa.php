<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'nit',
        'direccion',
        'telefono',
        'moneda',
        'actividad_economica',
        'ciudad',
        'departamento',
        'logo',
        'user_id'
    ];

    protected $casts = [
        'moneda' => 'string',
    ];

    // Relación con el usuario administrador
    public function usuarioAdmin()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    // Scope para filtrar empresas por usuario
    public function scopeForUser($query, $user)
    {
        if ($user->hasRole('Super Admin')) {
            return $query;
        }
        return $query->where('user_id', $user->id);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'user_id'); // Usuario que la creó
    }

    public function periodos()
    {
        return $this->hasMany(Periodo::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }



}
