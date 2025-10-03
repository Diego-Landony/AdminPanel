<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para crear una variante de producto
 */
class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'category_id' => 'required|exists:categories,id',
            'sku' => 'nullable|string|max:50|unique:product_variants,sku',

            // 4 precios base obligatorios
            'base_price' => 'required|numeric|min:0',
            'delivery_price' => 'required|numeric|min:0',
            'interior_base_price' => 'required|numeric|min:0',
            'interior_delivery_price' => 'required|numeric|min:0',

            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'El producto es obligatorio.',
            'product_id.exists' => 'El producto seleccionado no existe.',
            'category_id.required' => 'La categoría/tamaño es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'sku.unique' => 'Ya existe una variante con este SKU.',

            'base_price.required' => 'El precio pickup es obligatorio.',
            'base_price.min' => 'El precio debe ser mayor o igual a 0.',
            'delivery_price.required' => 'El precio delivery es obligatorio.',
            'interior_base_price.required' => 'El precio interior pickup es obligatorio.',
            'interior_delivery_price.required' => 'El precio interior delivery es obligatorio.',
        ];
    }
}
