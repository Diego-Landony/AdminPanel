<?php

namespace App\Http\Requests\Api\V1\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetDeliveryAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_address_id' => [
                'required',
                'integer',
                Rule::exists('customer_addresses', 'id')
                    ->where('customer_id', auth()->id()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_address_id.required' => 'La dirección de entrega es requerida',
            'delivery_address_id.exists' => 'La dirección seleccionada no existe o no te pertenece',
        ];
    }
}
