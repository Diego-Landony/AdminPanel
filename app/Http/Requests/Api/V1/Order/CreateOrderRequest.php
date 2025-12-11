<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'restaurant_id' => [
                'required',
                'integer',
                Rule::exists('restaurants', 'id'),
            ],
            'service_type' => [
                'required',
                'string',
                Rule::in(['pickup', 'delivery']),
            ],
            'delivery_address_id' => [
                'nullable',
                'integer',
                Rule::exists('customer_addresses', 'id'),
                'required_if:service_type,delivery',
            ],
            'payment_method' => [
                'required',
                'string',
                Rule::in(['cash', 'card', 'online']),
            ],
            'nit_id' => [
                'nullable',
                'integer',
                Rule::exists('customer_nits', 'id'),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
            'points_to_redeem' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'restaurant_id.required' => 'El restaurante es requerido.',
            'restaurant_id.exists' => 'El restaurante seleccionado no existe.',
            'service_type.required' => 'El tipo de servicio es requerido.',
            'service_type.in' => 'El tipo de servicio debe ser pickup o delivery.',
            'delivery_address_id.required_if' => 'La dirección de entrega es requerida para el servicio a domicilio.',
            'delivery_address_id.exists' => 'La dirección seleccionada no existe.',
            'payment_method.required' => 'El método de pago es requerido.',
            'payment_method.in' => 'El método de pago debe ser cash, card u online.',
            'nit_id.exists' => 'El NIT seleccionado no existe.',
            'notes.string' => 'Las notas deben ser texto.',
            'notes.max' => 'Las notas no pueden exceder 500 caracteres.',
            'points_to_redeem.integer' => 'Los puntos a redimir deben ser un número entero.',
            'points_to_redeem.min' => 'Los puntos a redimir deben ser al menos 0.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'restaurant_id' => 'restaurante',
            'service_type' => 'tipo de servicio',
            'delivery_address_id' => 'dirección de entrega',
            'payment_method' => 'método de pago',
            'nit_id' => 'NIT',
            'notes' => 'notas',
            'points_to_redeem' => 'puntos a redimir',
        ];
    }
}
