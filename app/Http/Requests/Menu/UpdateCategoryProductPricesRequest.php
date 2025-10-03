<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para actualizar los precios de un producto en una categoría
 * (solo para categorías que NO usan variantes)
 */
class UpdateCategoryProductPricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('menu.categories.edit');
    }

    public function rules(): array
    {
        return [
            'precio_pickup_capital' => 'required|numeric|min:0',
            'precio_domicilio_capital' => 'required|numeric|min:0',
            'precio_pickup_interior' => 'required|numeric|min:0',
            'precio_domicilio_interior' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'precio_pickup_capital.required' => 'El precio pickup capital es obligatorio.',
            'precio_pickup_capital.numeric' => 'El precio debe ser un número.',
            'precio_pickup_capital.min' => 'El precio debe ser mayor o igual a 0.',

            'precio_domicilio_capital.required' => 'El precio domicilio capital es obligatorio.',
            'precio_domicilio_capital.numeric' => 'El precio debe ser un número.',
            'precio_domicilio_capital.min' => 'El precio debe ser mayor o igual a 0.',

            'precio_pickup_interior.required' => 'El precio pickup interior es obligatorio.',
            'precio_pickup_interior.numeric' => 'El precio debe ser un número.',
            'precio_pickup_interior.min' => 'El precio debe ser mayor o igual a 0.',

            'precio_domicilio_interior.required' => 'El precio domicilio interior es obligatorio.',
            'precio_domicilio_interior.numeric' => 'El precio debe ser un número.',
            'precio_domicilio_interior.min' => 'El precio debe ser mayor o igual a 0.',
        ];
    }
}
