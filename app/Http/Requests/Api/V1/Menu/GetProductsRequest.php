<?php

namespace App\Http\Requests\Api\V1\Menu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetProductsRequest extends FormRequest
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
            'has_variants' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'zone' => ['nullable', Rule::in(['capital', 'interior'])],
            'service_type' => ['nullable', Rule::in(['pickup', 'delivery'])],
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
            'has_variants.boolean' => 'El campo tiene variantes debe ser verdadero o falso.',
            'is_active.boolean' => 'El campo activo debe ser verdadero o falso.',
            'per_page.integer' => 'Los resultados por página debe ser un número entero.',
            'per_page.min' => 'Los resultados por página debe ser al menos 1.',
            'per_page.max' => 'Los resultados por página no puede exceder 100.',
            'zone.in' => 'La zona debe ser: capital o interior.',
            'service_type.in' => 'El tipo de servicio debe ser: pickup o delivery.',
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
            'has_variants' => 'tiene variantes',
            'is_active' => 'activo',
            'per_page' => 'resultados por página',
            'zone' => 'zona',
            'service_type' => 'tipo de servicio',
        ];
    }
}
