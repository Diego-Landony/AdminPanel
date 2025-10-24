<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComboRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $comboId = $this->route('combo');

        return [
            // Información básica
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('combos', 'name')->ignore($comboId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'string', 'max:255'],

            // Precios del combo (4 precios requeridos)
            'precio_pickup_capital' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'precio_domicilio_capital' => [
                'required',
                'numeric',
                'min:0',
                'max:9999.99',
                'gte:precio_pickup_capital',
            ],
            'precio_pickup_interior' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'precio_domicilio_interior' => [
                'required',
                'numeric',
                'min:0',
                'max:9999.99',
                'gte:precio_pickup_interior',
            ],

            // Configuración
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            // Items del combo (mínimo 2, sin máximo)
            'items' => ['required', 'array', 'min:2'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            // Categoría
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',

            // Nombre
            'name.required' => 'El nombre del combo es obligatorio.',
            'name.unique' => 'Ya existe un combo con este nombre.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',

            // Descripción
            'description.max' => 'La descripción no puede tener más de 500 caracteres.',

            // Precios
            'precio_pickup_capital.required' => 'El precio de pickup en capital es obligatorio.',
            'precio_pickup_capital.min' => 'El precio debe ser mayor o igual a 0.',
            'precio_domicilio_capital.required' => 'El precio de delivery en capital es obligatorio.',
            'precio_domicilio_capital.gte' => 'El precio de delivery debe ser mayor o igual al precio de pickup.',
            'precio_pickup_interior.required' => 'El precio de pickup en interior es obligatorio.',
            'precio_domicilio_interior.required' => 'El precio de delivery en interior es obligatorio.',
            'precio_domicilio_interior.gte' => 'El precio de delivery debe ser mayor o igual al precio de pickup.',

            // Items
            'items.required' => 'Debes agregar al menos 2 productos al combo.',
            'items.min' => 'Un combo debe tener al menos 2 productos.',

            // Items individuales
            'items.*.product_id.required' => 'El producto es obligatorio.',
            'items.*.product_id.exists' => 'El producto seleccionado no existe.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1.',
            'items.*.quantity.max' => 'La cantidad no puede ser mayor a 10.',
        ];
    }

    /**
     * Validación adicional después de las reglas básicas
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Si se intenta activar el combo, verificar que todos los productos estén activos
            if ($this->is_active) {
                $this->validateActiveProducts($validator);
            }

            // Validar que la categoría sea de tipo combo
            if ($this->category_id) {
                $this->validateComboCategory($validator);
            }

            // Validar que los productos con variantes tengan variant_id
            if ($this->items) {
                $this->validateVariantRequirements($validator);
            }
        });
    }

    /**
     * Valida que todos los productos del combo estén activos
     */
    protected function validateActiveProducts($validator): void
    {
        $productIds = collect($this->items)->pluck('product_id')->unique();

        $inactiveProducts = \App\Models\Menu\Product::whereIn('id', $productIds)
            ->where('is_active', false)
            ->pluck('name');

        if ($inactiveProducts->isNotEmpty()) {
            $validator->errors()->add(
                'is_active',
                'No puedes activar el combo porque contiene productos inactivos: '.$inactiveProducts->join(', ')
            );
        }
    }

    /**
     * Valida que la categoría sea de tipo combo
     */
    protected function validateComboCategory($validator): void
    {
        $category = \App\Models\Menu\Category::find($this->category_id);

        if ($category && ! $category->is_combo_category) {
            $validator->errors()->add(
                'category_id',
                'La categoría seleccionada debe ser de tipo combo.'
            );
        }
    }

    /**
     * Valida que los productos con variantes tengan variant_id y viceversa
     */
    protected function validateVariantRequirements($validator): void
    {
        foreach ($this->items as $index => $item) {
            $product = \App\Models\Menu\Product::find($item['product_id']);

            if (! $product) {
                continue;
            }

            // Si el producto tiene variantes, variant_id es requerido
            if ($product->has_variants) {
                if (empty($item['variant_id'])) {
                    $validator->errors()->add(
                        "items.{$index}.variant_id",
                        "Debes seleccionar una variante para {$product->name}."
                    );
                } else {
                    // Validar que la variante pertenece al producto
                    $variantExists = $product->variants()
                        ->where('id', $item['variant_id'])
                        ->exists();

                    if (! $variantExists) {
                        $validator->errors()->add(
                            "items.{$index}.variant_id",
                            "La variante seleccionada no pertenece a {$product->name}."
                        );
                    }
                }
            } else {
                // Si el producto NO tiene variantes, variant_id debe ser null
                if (! empty($item['variant_id'])) {
                    $validator->errors()->add(
                        "items.{$index}.variant_id",
                        "{$product->name} no tiene variantes. No debes seleccionar una variante."
                    );
                }
            }
        }
    }
}
