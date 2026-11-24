<?php

namespace App\Http\Requests\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnidadMedidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Aquí va tu lógica de permisos por empresa
    }

    public function rules(): array
    {
        $unidadId = $this->route('unidad_medida');

        return [
            'codigo' => [
                'required',
                'string',
                'max:10',
                Rule::unique('unidades_medida', 'codigo')->ignore($unidadId),
            ],
            'nombre' => 'required|string|max:60',
            'descripcion' => 'nullable|string|max:100',
            'activo' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio (ej: PZA, KG, LT).',
            'codigo.unique' => 'Ya existe una unidad con ese código.',
            'nombre.required' => 'El nombre de la unidad es obligatorio.',
        ];
    }
}
