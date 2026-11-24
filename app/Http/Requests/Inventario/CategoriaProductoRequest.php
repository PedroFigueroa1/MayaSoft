<?php

namespace App\Http\Requests\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoriaId = $this->route('categoria_producto');

        return [
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('categorias_productos', 'codigo')->ignore($categoriaId),
            ],
            'nombre' => 'required|string|max:100',
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categorias_productos,id',
                // No puede ser su propio padre ni descendiente
                function ($attribute, $value, $fail) use ($categoriaId) {
                    if ($value && $value == $categoriaId) {
                        $fail('Una categoría no puede ser su propio padre.');
                    }
                },
            ],
            'activo' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código de categoría es obligatorio.',
            'codigo.unique' => 'Ya existe una categoría con ese código.',
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'parent_id.exists' => 'La categoría padre seleccionada no existe.',
        ];
    }
}
