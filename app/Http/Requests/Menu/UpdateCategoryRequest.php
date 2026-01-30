<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')->ignore($categoryId),
            ],
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'is_combo_category' => 'boolean',
            'uses_variants' => 'boolean',
            'variant_definitions' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    // Si uses_variants es true, variant_definitions no puede estar vacío
                    if ($this->input('uses_variants') && empty($value)) {
                        $fail('Debes definir al menos una variante cuando uses variantes.');
                    }

                    // Validar que no haya duplicados
                    if (is_array($value) && count($value) !== count(array_unique($value))) {
                        $fail('Las variantes no pueden tener nombres duplicados.');
                    }
                },
            ],
            'variant_definitions.*' => 'string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.max' => 'El nombre no puede exceder 100 caracteres.',
            'name.unique' => 'Ya existe una categoría con este nombre.',
            'uses_variants.boolean' => 'El campo usa variantes debe ser verdadero o falso.',
            'variant_definitions.array' => 'Las definiciones de variantes deben ser un arreglo.',
            'variant_definitions.*.string' => 'Cada variante debe ser texto.',
            'variant_definitions.*.max' => 'Cada variante no puede exceder 50 caracteres.',
        ];
    }
}
