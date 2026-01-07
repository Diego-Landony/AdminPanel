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
                Rule::requiredIf(fn () => $this->service_type === 'delivery'),
                'nullable',
                'integer',
                Rule::exists('customer_addresses', 'id')->where('customer_id', auth()->id()),
            ],
            'scheduled_pickup_time' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    if ($this->service_type === 'pickup' && $value) {
                        $scheduledTime = \Carbon\Carbon::parse($value);
                        $minimumTime = now()->addMinutes(30);

                        if ($scheduledTime->lt($minimumTime)) {
                            $fail('La hora de recogida debe ser al menos 30 minutos desde ahora.');
                        }
                    }
                },
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
            'delivery_address_id.required' => 'La dirección de entrega es requerida para pedidos delivery.',
            'delivery_address_id.exists' => 'La dirección seleccionada no existe o no te pertenece.',
            'scheduled_pickup_time.date' => 'La hora de recogida debe ser una fecha válida.',
            'payment_method.required' => 'El método de pago es requerido.',
            'payment_method.in' => 'El método de pago debe ser cash, card u online.',
            'nit_id.exists' => 'El NIT seleccionado no existe.',
            'notes.string' => 'Las notas deben ser texto.',
            'notes.max' => 'Las notas no pueden exceder 500 caracteres.',
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
            'scheduled_pickup_time' => 'hora de recogida',
            'payment_method' => 'método de pago',
            'nit_id' => 'NIT',
            'notes' => 'notas',
        ];
    }
}
