<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SiatPuntoVentaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre'          => 'required|string|max:100',
            'cod_sucursal'    => 'required|integer|min:0|max:9999',
            'cod_punto_venta' => 'required|integer|min:0|max:9999',
            'direccion'       => 'nullable|string|max:250',
            'municipio'       => 'nullable|string|max:100',
            'telefono'        => 'nullable|string|max:30',
            'activo'          => 'boolean',
            'usuarios'        => 'nullable|array',
            'usuarios.*.id'   => 'exists:users,id',
            'usuarios.*.por_defecto' => 'boolean',
        ];
    }
}
