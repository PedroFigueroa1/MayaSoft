<?php

namespace App\Http\Requests\Inventario;

use Illuminate\Foundation\Http\FormRequest;

class CompraRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'proveedor_id'     => ['required', 'exists:proveedores_clientes,id'],
            'almacen_id'       => ['required', 'exists:almacenes,id'],
            'nro_factura'      => ['nullable', 'string', 'max:30'],
            'cod_autorizacion' => ['nullable', 'string', 'max:100'],
            'fecha_factura'    => ['nullable', 'date'],
            'fecha_recepcion'  => ['required', 'date'],
            'descuento'        => ['nullable', 'numeric', 'min:0'],
            'forma_pago'       => ['required', 'in:contado,credito,mixto'],
            'dias_credito'     => ['nullable', 'integer', 'min:0'],
            'cuenta_pago_id'   => ['nullable', 'exists:cuentas,id'],
            'cuenta_iva_cf_id' => ['nullable', 'exists:cuentas,id'],
            'observacion'      => ['nullable', 'string'],
            // Detalle
            'detalles'              => ['required', 'array', 'min:1'],
            'detalles.*.producto_id'=> ['required', 'exists:productos,id', 'distinct'],
            'detalles.*.cantidad'   => ['required', 'numeric', 'gt:0'],
            'detalles.*.costo_unitario' => ['required', 'numeric', 'gte:0'],
            'detalles.*.descuento'  => ['nullable', 'numeric', 'gte:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'descuento'    => $this->input('descuento', 0),
            'dias_credito' => $this->input('dias_credito', 0),
        ]);
    }
}
