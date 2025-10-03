<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para asociar un producto a una categoría
 *
 * - Si la categoría NO usa variantes: se requieren los 4 precios
 * - Si la categoría SÍ usa variantes: los precios son opcionales (se asignarán después en las variantes)
 */
class AttachProductToCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('menu.categories.edit');
    }

    public function rules(): array
    {
        $category = $this->route('category');

        $priceValidation = $category->uses_variants ? 'nullable' : 'required';

        return [
            'product_id' => 'required|exists:products,id',
            'sort_order' => 'nullable|integer|min:0',

            // Precios solo requeridos si la categoría NO usa variantes
            'precio_pickup_capital' => "{$priceValidation}|numeric|min:0",
            'precio_domicilio_capital' => "{$priceValidation}|numeric|min:0",
            'precio_pickup_interior' => "{$priceValidation}|numeric|min:0",
            'precio_domicilio_interior' => "{$priceValidation}|numeric|min:0",
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'El producto es obligatorio.',
            'product_id.exists' => 'El producto seleccionado no existe.',

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

            'sort_order.integer' => 'El orden debe ser un número entero.',
            'sort_order.min' => 'El orden debe ser mayor o igual a 0.',
        ];
    }
}
