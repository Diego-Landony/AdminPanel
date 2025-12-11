<?php

namespace App\Http\Requests\Api\V1\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddCartItemRequest extends FormRequest
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
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id'),
                'required_without:combo_id',
            ],
            'combo_id' => [
                'nullable',
                'integer',
                Rule::exists('combos', 'id'),
                'required_without:product_id',
            ],
            'variant_id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id'),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'selected_options' => ['nullable', 'array'],
            'selected_options.*.section_id' => ['required', 'integer'],
            'selected_options.*.option_id' => ['required', 'integer'],
            'combo_selections' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:500'],
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
            'product_id.required_without' => 'Debe seleccionar un producto o un combo.',
            'product_id.exists' => 'El producto seleccionado no existe.',
            'combo_id.required_without' => 'Debe seleccionar un producto o un combo.',
            'combo_id.exists' => 'El combo seleccionado no existe.',
            'variant_id.exists' => 'La variante seleccionada no existe.',
            'quantity.required' => 'La cantidad es requerida.',
            'quantity.integer' => 'La cantidad debe ser un número entero.',
            'quantity.min' => 'La cantidad debe ser al menos 1.',
            'quantity.max' => 'La cantidad no puede exceder 10 unidades.',
            'selected_options.array' => 'Las opciones seleccionadas deben ser un arreglo.',
            'selected_options.*.section_id.required' => 'El ID de la sección es requerido.',
            'selected_options.*.section_id.integer' => 'El ID de la sección debe ser un número entero.',
            'selected_options.*.option_id.required' => 'El ID de la opción es requerido.',
            'selected_options.*.option_id.integer' => 'El ID de la opción debe ser un número entero.',
            'combo_selections.array' => 'Las selecciones del combo deben ser un arreglo.',
            'notes.string' => 'Las notas deben ser texto.',
            'notes.max' => 'Las notas no pueden exceder 500 caracteres.',
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
            'product_id' => 'producto',
            'combo_id' => 'combo',
            'variant_id' => 'variante',
            'quantity' => 'cantidad',
            'selected_options' => 'opciones seleccionadas',
            'combo_selections' => 'selecciones del combo',
            'notes' => 'notas',
        ];
    }
}
