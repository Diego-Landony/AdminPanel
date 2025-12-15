<?php

namespace App\Http\Requests\Api\V1\Points;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RedeemPointsRequest extends FormRequest
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
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')],
            'points_to_redeem' => ['required', 'integer', 'min:1'],
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
            'order_id.required' => 'El ID de la orden es requerido.',
            'order_id.integer' => 'El ID de la orden debe ser un número entero.',
            'order_id.exists' => 'La orden especificada no existe.',
            'points_to_redeem.required' => 'La cantidad de puntos a redimir es requerida.',
            'points_to_redeem.integer' => 'La cantidad de puntos debe ser un número entero.',
            'points_to_redeem.min' => 'Debe redimir al menos 1 punto.',
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
            'order_id' => 'orden',
            'points_to_redeem' => 'puntos a redimir',
        ];
    }
}
