<?php

namespace App\Http\Requests\CustomerType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerTypeRequest extends FormRequest
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
            'name' => 'required|string|max:100',
            'points_required' => 'required|integer|min:0',
            'multiplier' => 'required|numeric|min:1|max:10',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
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
            'name.required' => 'El nombre del tipo de cliente es obligatorio',
            'name.max' => 'El nombre no puede exceder 100 caracteres',
            'points_required.required' => 'Los puntos requeridos son obligatorios',
            'points_required.integer' => 'Los puntos requeridos deben ser un número entero',
            'points_required.min' => 'Los puntos requeridos no pueden ser negativos',
            'multiplier.required' => 'El multiplicador es obligatorio',
            'multiplier.numeric' => 'El multiplicador debe ser un número',
            'multiplier.min' => 'El multiplicador debe ser al menos 1',
            'multiplier.max' => 'El multiplicador no puede exceder 10',
            'color.max' => 'El color no puede exceder 20 caracteres',
            'is_active.boolean' => 'El estado debe ser verdadero o falso',
        ];
    }
}
