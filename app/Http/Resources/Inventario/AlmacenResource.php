<?php

namespace App\Http\Resources\Inventario;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlmacenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'direccion' => $this->direccion,
            'responsable' => $this->responsable,
            'cuenta_id' => $this->cuenta_id,
            'cuenta' => $this->whenLoaded('cuenta', function () {
                return [
                    'id' => $this->cuenta->id,
                    'codigo' => $this->cuenta->codigo,
                    'nombre' => $this->cuenta->nombre,
                ];
            }),
            'es_punto_venta' => $this->es_punto_venta,
            'es_produccion' => $this->es_produccion,
            'tipo' => $this->tipoDescripcion(),
            'activo' => $this->activo,
            'stock_count' => $this->whenCounted('stockActual'),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    /**
     * Descripción legible del tipo de almacén
     */
    private function tipoDescripcion(): string
    {
        if ($this->es_produccion) return 'Planta de Producción';
        if ($this->es_punto_venta) return 'Punto de Venta';
        return 'Depósito / Almacén';
    }
}