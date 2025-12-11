<?php

namespace App\Http\Requests\Api\V1\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
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
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'selected_options' => ['sometimes', 'array'],
            'selected_options.*.section_id' => ['required', 'integer'],
            'selected_options.*.option_id' => ['required', 'integer'],
            'combo_selections' => ['sometimes', 'array'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
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
            'quantity' => 'cantidad',
            'selected_options' => 'opciones seleccionadas',
            'combo_selections' => 'selecciones del combo',
            'notes' => 'notas',
        ];
    }
}
