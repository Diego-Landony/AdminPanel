<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:categories,name',
            'image' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'uses_variants' => 'boolean',
            'variant_definitions' => 'nullable|array',
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
