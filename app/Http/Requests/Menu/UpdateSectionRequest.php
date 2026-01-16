<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
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
            'bundle_discount_enabled' => 'boolean',
            'bundle_size' => 'required_if:bundle_discount_enabled,true|integer|min:2|max:10',
            'bundle_discount_amount' => 'required_if:bundle_discount_enabled,true|nullable|numeric|min:0.01',
            'is_active' => 'boolean',
            'options' => 'nullable|array',
            'options.*.id' => 'nullable|integer|exists:section_options,id',
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
            'bundle_size.required_if' => 'La cantidad para bundle es requerida cuando el descuento está habilitado.',
            'bundle_size.min' => 'La cantidad para bundle debe ser al menos 2.',
            'bundle_size.max' => 'La cantidad para bundle no puede exceder 10.',
            'bundle_discount_amount.required_if' => 'El monto de descuento es requerido cuando el descuento está habilitado.',
            'bundle_discount_amount.min' => 'El monto de descuento debe ser mayor a 0.',
            'options.*.name.required' => 'El nombre de la opción es obligatorio.',
        ];
    }
}
