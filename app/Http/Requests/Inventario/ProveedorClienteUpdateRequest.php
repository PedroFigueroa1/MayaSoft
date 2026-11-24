<?php

namespace App\Http\Requests\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedorClienteUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('proveedor_cliente');

        return [
            'nit' => ['nullable', 'string', 'max:20', Rule::unique('proveedores_clientes')->ignore($id)->where('empresa_id', auth()->user()->empresa_id)],
            'ci' => ['nullable', 'string', 'max:15'],
            'complemento' => ['nullable', 'string', 'max:5'],
            'razon_social' => ['required', 'string', 'max:200'],
            'nombre_comercial' => ['nullable', 'string', 'max:200'],
            'es_proveedor' => ['boolean'],
            'es_cliente' => ['boolean'],
            'email' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:250'],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'departamento' => ['nullable', 'string', 'max:60'],
            'cuenta_por_cobrar_id' => ['nullable', 'exists:cuentas,id'],
            'cuenta_por_pagar_id' => ['nullable', 'exists:cuentas,id'],
            'dias_credito' => ['required', 'integer', 'min:0'],
            'limite_credito' => ['required', 'numeric', 'min:0'],
            'activo' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->boolean('es_proveedor') && ! $this->boolean('es_cliente')) {
                $validator->errors()->add('tipo', 'Debe marcar al menos una opción: Proveedor o Cliente.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'es_proveedor' => $this->boolean('es_proveedor'),
            'es_cliente' => $this->boolean('es_cliente'),
            'activo' => $this->boolean('activo'),
        ]);
    }
}
