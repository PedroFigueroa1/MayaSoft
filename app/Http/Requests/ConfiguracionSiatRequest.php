<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfiguracionSiatRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('configuracion_siat')?->id;

        return [
            'cod_ambiente'     => 'required|in:1,2',
            'cod_modalidad'    => 'required|in:1,2,3',
            'cod_tipo_factura' => 'required|in:1',
            'cod_sistema'      => 'nullable|string|max:100',
            'token_delegado'   => 'nullable|string|max:2000',
            'url_api'          => 'nullable|url|max:200',
            'activo'           => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'cod_ambiente'     => 'ambiente',
            'cod_modalidad'    => 'modalidad',
            'cod_tipo_factura' => 'tipo de factura',
            'cod_sistema'      => 'código de sistema',
            'token_delegado'   => 'token delegado',
        ];
    }
}
