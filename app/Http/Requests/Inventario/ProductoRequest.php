<?php

namespace App\Http\Requests\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('producto');

        return [
            'codigo' => ['required', 'string', 'max:30', Rule::unique('productos')->ignore($id)],
            'codigo_barra' => ['nullable', 'string', 'max:50', Rule::unique('productos')->ignore($id)],
            'descripcion' => ['required', 'string', 'max:200'],
            'descripcion_larga' => ['nullable', 'string'],
            'categoria_id' => ['nullable', 'exists:categorias_productos,id'],
            'unidad_medida_id' => ['required', 'exists:unidades_medida,id'],
            'tipo' => ['required', Rule::in(['mercaderia', 'materia_prima', 'producto_terminado', 'servicio', 'activo_fijo'])],
            'precio_costo' => ['required', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'precio_venta_2' => ['nullable', 'numeric', 'min:0'],
            'precio_venta_3' => ['nullable', 'numeric', 'min:0'],
            'aplica_iva' => ['boolean'],
            'porcentaje_iva' => ['required_if:aplica_iva,1', 'numeric', 'min:0', 'max:100'],
            'metodo_costeo' => ['required', Rule::in(['PROM', 'PEPS', 'ESP'])],
            'cuenta_activo_id' => ['nullable', 'exists:cuentas,id'],
            'cuenta_costo_venta_id' => ['nullable', 'exists:cuentas,id'],
            'cuenta_ingreso_id' => ['nullable', 'exists:cuentas,id'],
            'cuenta_variacion_id' => ['nullable', 'exists:cuentas,id'],
            'stock_minimo' => ['required', 'numeric', 'min:0'],
            'stock_maximo' => ['required', 'numeric', 'min:0'],
            'controla_stock' => ['boolean'],
            'codigo_sin' => ['nullable', 'string', 'max:20'],
            'descripcion_sin' => ['nullable', 'string', 'max:200'],
            'activo' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'aplica_iva' => $this->boolean('aplica_iva'),
            'controla_stock' => $this->boolean('controla_stock'),
            'activo' => $this->boolean('activo', true),
            'precio_venta_2' => $this->input('precio_venta_2', 0),
            'precio_venta_3' => $this->input('precio_venta_3', 0),
        ]);
    }
}
