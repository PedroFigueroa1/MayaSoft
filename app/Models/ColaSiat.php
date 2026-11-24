<?php
namespace App\Models;
 
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
 
class ColaSiat extends Model
{
    use BelongsToEmpresa;
 
    public const MAX_INTENTOS = 5;
 
    protected $table = 'cola_siat';
    protected $fillable = [
        'empresa_id', 'venta_id', 'payload_xml', 'cuf',
        'intentos', 'ultimo_intento_at', 'ultimo_error', 'estado',
    ];
    protected $casts = ['ultimo_intento_at' => 'datetime'];
 
    public function venta(): BelongsTo { return $this->belongsTo(Venta::class); }
 
    // Lo que el job debe tomar: pendientes con intentos disponibles
    public function scopePorEnviar($q)
    {
        return $q->where('estado', 'pendiente')->where('intentos', '<', self::MAX_INTENTOS);
    }
    public function scopeConErrores($q) { return $q->where('estado', 'rechazada'); }
 
    public function puedeReintentar(): bool
    {
        return $this->intentos < self::MAX_INTENTOS && in_array($this->estado, ['pendiente']);
    }
 
    /** Falló la CONEXIÓN: sigue pendiente, se reintenta luego. */
    public function falloConexion(string $error): void
    {
        $agotado = $this->intentos + 1 >= self::MAX_INTENTOS;
        $this->update([
            'intentos'          => $this->intentos + 1,
            'ultimo_intento_at' => now(),
            'ultimo_error'      => $error,
            'estado'            => $agotado ? 'rechazada' : 'pendiente',
        ]);
    }
 
    /** El SIN RECHAZÓ la factura: reintentar igual no sirve, requiere corrección. */
    public function rechazadaPorSiat(string $error): void
    {
        $this->update([
            'intentos'          => $this->intentos + 1,
            'ultimo_intento_at' => now(),
            'ultimo_error'      => $error,
            'estado'            => 'rechazada',
        ]);
    }
 
    public function aprobada(): void
    {
        $this->update([
            'intentos'          => $this->intentos + 1,
            'ultimo_intento_at' => now(),
            'ultimo_error'      => null,
            'estado'            => 'aprobada',
        ]);
    }
}