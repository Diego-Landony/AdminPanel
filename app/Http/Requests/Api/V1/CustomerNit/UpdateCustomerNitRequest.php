<?php

namespace App\Http\Requests\Api\V1\CustomerNit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerNitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $nitId = $this->route('nit')?->id;

        return [
            'nit' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customer_nits', 'nit')
                    ->where('customer_id', auth()->id())
                    ->ignore($nitId),
            ],
            'nit_type' => ['nullable', 'string', Rule::in(['personal', 'company', 'other'])],
            'nit_name' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nit.required' => 'El NIT es requerido',
            'nit.max' => 'El NIT no debe exceder 20 caracteres',
            'nit.unique' => 'Este NIT ya está registrado en tu cuenta',
            'nit_type.in' => 'El tipo de NIT debe ser: personal, company o other',
            'nit_name.max' => 'El nombre del NIT no debe exceder 255 caracteres',
        ];
    }
}
