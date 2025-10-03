<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:150',
            'description' => 'nullable|string',
            'is_required' => 'boolean',
            'allow_multiple' => 'boolean',
            'min_selections' => 'required|integer|min:0',
            'max_selections' => 'required|integer|min:1',
            'options' => 'nullable|array',
            'options.*.name' => 'required|string|max:100',
            'options.*.is_extra' => 'boolean',
            'options.*.price_modifier' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título de la sección es obligatorio.',
            'title.max' => 'El título no puede exceder 150 caracteres.',
            'min_selections.required' => 'El mínimo de selecciones es obligatorio.',
            'min_selections.min' => 'El mínimo debe ser 0 o mayor.',
            'max_selections.required' => 'El máximo de selecciones es obligatorio.',
            'max_selections.min' => 'El máximo debe ser al menos 1.',
            'options.*.name.required' => 'El nombre de la opción es obligatorio.',
        ];
    }
}
