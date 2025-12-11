<?php

namespace App\Http\Requests\Api\V1\Menu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetMenuRequest extends FormRequest
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
            'zone' => ['nullable', Rule::in(['capital', 'interior'])],
            'service_type' => ['nullable', Rule::in(['pickup', 'delivery'])],
            'include_inactive' => ['nullable', 'boolean'],
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
            'zone.in' => 'La zona debe ser: capital o interior.',
            'service_type.in' => 'El tipo de servicio debe ser: pickup o delivery.',
            'include_inactive.boolean' => 'El campo incluir inactivos debe ser verdadero o falso.',
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
            'zone' => 'zona',
            'service_type' => 'tipo de servicio',
            'include_inactive' => 'incluir inactivos',
        ];
    }

    /**
     * Get validated data with defaults.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        return array_merge([
            'zone' => 'capital',
            'service_type' => 'pickup',
            'include_inactive' => false,
        ], $validated);
    }
}
