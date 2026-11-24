<?php

namespace App\Http\Resources\Inventario;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'parent_id' => $this->parent_id,
            'padre' => $this->whenLoaded('padre', function () {
                return [
                    'id' => $this->padre->id,
                    'nombre' => $this->padre->nombre,
                ];
            }),
            'hijos' => CategoriaProductoResource::collection($this->whenLoaded('hijos')),
            'descendientes' => CategoriaProductoResource::collection($this->whenLoaded('descendientes')),
            'productos_count' => $this->whenCounted('productos'),
            'activo' => $this->activo,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
