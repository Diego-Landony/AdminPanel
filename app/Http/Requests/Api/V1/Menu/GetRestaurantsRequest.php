<?php

namespace App\Http\Requests\Api\V1\Menu;

use Illuminate\Foundation\Http\FormRequest;

class GetRestaurantsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'delivery_active' => ['nullable', 'boolean'],
            'pickup_active' => ['nullable', 'boolean'],
            'is_open' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
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
            'delivery_active.boolean' => 'El campo delivery activo debe ser verdadero o falso.',
            'pickup_active.boolean' => 'El campo pickup activo debe ser verdadero o falso.',
            'is_open.boolean' => 'El campo abierto debe ser verdadero o falso.',
            'per_page.integer' => 'Los resultados por página debe ser un número entero.',
            'per_page.min' => 'Los resultados por página debe ser al menos 1.',
            'per_page.max' => 'Los resultados por página no puede exceder 100.',
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
            'delivery_active' => 'delivery activo',
            'pickup_active' => 'pickup activo',
            'is_open' => 'abierto',
            'per_page' => 'resultados por página',
        ];
    }
}
