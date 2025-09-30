<?php

namespace App\Http\Requests\Restaurant;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'boolean',
            'delivery_active' => 'boolean',
            'pickup_active' => 'boolean',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'schedule' => 'nullable|array',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'estimated_delivery_time' => 'nullable|integer|min:1',
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
            'name.required' => 'El nombre del restaurante es obligatorio',
            'name.max' => 'El nombre no puede exceder 255 caracteres',
            'address.required' => 'La dirección es obligatoria',
            'address.max' => 'La dirección no puede exceder 255 caracteres',
            'latitude.numeric' => 'La latitud debe ser un número válido',
            'latitude.between' => 'La latitud debe estar entre -90 y 90',
            'longitude.numeric' => 'La longitud debe ser un número válido',
            'longitude.between' => 'La longitud debe estar entre -180 y 180',
            'is_active.boolean' => 'El estado debe ser verdadero o falso',
            'delivery_active.boolean' => 'El estado de delivery debe ser verdadero o falso',
            'pickup_active.boolean' => 'El estado de pickup debe ser verdadero o falso',
            'phone.max' => 'El teléfono no puede exceder 255 caracteres',
            'email.email' => 'El correo electrónico debe ser válido',
            'email.max' => 'El correo electrónico no puede exceder 255 caracteres',
            'schedule.array' => 'El horario debe ser un formato válido',
            'minimum_order_amount.numeric' => 'El monto mínimo debe ser un número',
            'minimum_order_amount.min' => 'El monto mínimo no puede ser negativo',
            'estimated_delivery_time.integer' => 'El tiempo de entrega debe ser un número entero',
            'estimated_delivery_time.min' => 'El tiempo de entrega debe ser al menos 1 minuto',
        ];
    }
}
