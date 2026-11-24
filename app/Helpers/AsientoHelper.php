<?php

namespace App\Helpers;

use App\Models\Asiento;
use App\Models\Periodo;

class AsientoHelper
{
    /**
     * Prefijos para cada tipo de asiento
     */
    private static $prefijos = [
        'ING' => 'CI-',  // Comprobante de Ingreso
        'EGR' => 'CE-',  // Comprobante de Egreso
        'TRN' => 'CT-',  // Comprobante de Traspaso
        'AJT' => 'CA-',  // Comprobante de Ajuste
        'CIE' => 'CC-',  // Comprobante de Cierre
    ];

    /**
     * Genera el próximo número de asiento para un tipo específico en un período
     */

    public static function generarNumeroAsiento($periodoId, $tipo)
    {
        if (!array_key_exists($tipo, self::$prefijos)) {
            throw new \InvalidArgumentException("Tipo de asiento no válido: {$tipo}");
        }

        $periodo = Periodo::find($periodoId);
        if (!$periodo) {
            throw new \InvalidArgumentException("Período no encontrado: {$periodoId}");
        }

        $prefijo = self::$prefijos[$tipo];

        // 🔒 Lock pesimista: bloquea la fila hasta que termine la transacción
        $ultimoAsiento = Asiento::where('periodo_id', $periodoId)
            ->where('numero_asiento', 'LIKE', $prefijo . '%')
            ->orderByRaw("CAST(SUBSTRING(numero_asiento, ?) AS UNSIGNED) DESC", [strlen($prefijo) + 1])
            ->lockForUpdate()   // <-- esto es clave
            ->first();

        if (!$ultimoAsiento) {
            return $prefijo . '0001';
        }

        $numeroPart = substr($ultimoAsiento->numero_asiento, strlen($prefijo));
        $numeroPart = preg_replace('/[^0-9]/', '', $numeroPart);

        if (empty($numeroPart) || !is_numeric($numeroPart)) {
            return $prefijo . '0001';
        }

        $siguienteNumero = intval($numeroPart) + 1;
        $digitos = max(4, strlen((string)$siguienteNumero));

        return $prefijo . str_pad($siguienteNumero, $digitos, '0', STR_PAD_LEFT);
    }

    /**
     * Obtiene todos los prefijos
     */
    public static function getPrefijos()
    {
        return self::$prefijos;
    }

    /**
     * Obtiene el prefijo para un tipo específico
     */
    public static function getPrefijo($tipo)
    {
        return self::$prefijos[$tipo] ?? '';
    }

    /**
     * Obtiene el tipo por prefijo
     */
    public static function getTipoPorPrefijo($prefijo)
    {
        return array_search($prefijo, self::$prefijos);
    }

    /**
     * Obtiene el número base para un nuevo período
     * Si es un período nuevo sin asientos, empieza desde 1
     */
    public static function getNumeroBaseParaPeriodo($periodoId)
    {
        // Verificar si hay algún asiento en este período
        $tieneAsientos = Asiento::where('periodo_id', $periodoId)->exists();

        if (!$tieneAsientos) {
            return 1; // Período nuevo, empezar desde 1
        }

        return null; // Dejar que cada tipo maneje su propio contador
    }
}
