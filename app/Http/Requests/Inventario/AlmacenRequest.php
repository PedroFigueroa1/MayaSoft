<?php

namespace App\Http\Requests\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlmacenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $almacenId = $this->route('almacen');

        return [
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('almacenes', 'codigo')->ignore($almacenId),
            ],
            'nombre' => 'required|string|max:100',
            'direccion' => 'nullable|string|max:200',
            'responsable' => 'nullable|string|max:100',
            'cuenta_id' => 'nullable|integer|exists:cuentas,id',
            'es_punto_venta' => 'boolean',
            'es_produccion' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código del almacén es obligatorio.',
            'codigo.unique' => 'Ya existe un almacén con ese código.',
            'nombre.required' => 'El nombre del almacén es obligatorio.',
            'cuenta_id.exists' => 'La cuenta contable seleccionada no existe.',
        ];
    }

    /**
     * Preparar datos antes de validar
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'es_punto_venta' => $this->boolean('es_punto_venta', false),
            'es_produccion' => $this->boolean('es_produccion', false),
            'activo' => $this->boolean('activo', true),
        ]);
    }
}
