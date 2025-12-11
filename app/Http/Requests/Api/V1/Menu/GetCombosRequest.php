<?php

namespace App\Http\Requests\Api\V1\Menu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetCombosRequest extends FormRequest
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
            'category_id' => ['nullable', Rule::exists('categories', 'id')],
            'search' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
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
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'search.string' => 'El término de búsqueda debe ser texto.',
            'search.max' => 'El término de búsqueda no puede exceder 100 caracteres.',
            'is_active.boolean' => 'El campo activo debe ser verdadero o falso.',
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
            'category_id' => 'categoría',
            'search' => 'búsqueda',
            'is_active' => 'activo',
            'per_page' => 'resultados por página',
        ];
    }
}
