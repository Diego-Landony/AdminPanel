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
            'image' => ['nullable', 'file', 'max:5120', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/bmp,image/svg+xml,image/avif'],
            'remove_image' => ['nullable', 'boolean'],

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
            'items.*.is_choice_group' => ['boolean'],
            'items.*.choice_label' => ['nullable', 'string', 'max:255'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],

            // Opciones para grupos de elección
            'items.*.options' => ['nullable', 'array'],
            'items.*.options.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.options.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.options.*.sort_order' => ['nullable', 'integer', 'min:0'],
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
            'items.*.product_id.exists' => 'El producto seleccionado no existe.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1.',
            'items.*.quantity.max' => 'La cantidad no puede ser mayor a 10.',
            'items.*.choice_label.max' => 'La etiqueta no puede tener más de 255 caracteres.',

            // Opciones de grupos de elección
            'items.*.options.*.product_id.required' => 'El producto de la opción es obligatorio.',
            'items.*.options.*.product_id.exists' => 'El producto seleccionado no existe.',
        ];
    }

    /**
     * Validación adicional después de las reglas básicas
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validar que todos los productos estén activos (siempre, no solo cuando el combo esté activo)
            if ($this->items) {
                $this->validateActiveProducts($validator);
            }

            // Validar que la categoría sea de tipo combo
            if ($this->category_id) {
                $this->validateComboCategory($validator);
            }

            // Validar que los productos con variantes tengan variant_id
            if ($this->items) {
                $this->validateVariantRequirements($validator);
                $this->validateChoiceGroups($validator);
            }
        });
    }

    /**
     * Valida que todos los productos del combo estén activos
     */
    protected function validateActiveProducts($validator): void
    {
        foreach ($this->items as $itemIndex => $item) {
            $isChoiceGroup = $item['is_choice_group'] ?? false;

            if ($isChoiceGroup && isset($item['options'])) {
                // Para grupos de elección, validar cada opción
                foreach ($item['options'] as $optIndex => $option) {
                    if (isset($option['product_id'])) {
                        $product = \App\Models\Menu\Product::find($option['product_id']);

                        if ($product && ! $product->is_active) {
                            $validator->errors()->add(
                                "items.{$itemIndex}.options.{$optIndex}.product_id",
                                "El producto {$product->name} está inactivo."
                            );
                        }
                    }
                }
            } elseif (! $isChoiceGroup && isset($item['product_id'])) {
                // Para items fijos, validar el product_id
                $product = \App\Models\Menu\Product::find($item['product_id']);

                if ($product && ! $product->is_active) {
                    $validator->errors()->add(
                        "items.{$itemIndex}.product_id",
                        "El producto {$product->name} está inactivo."
                    );
                }
            }
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
            $isChoiceGroup = $item['is_choice_group'] ?? false;

            if ($isChoiceGroup) {
                // Para choice groups, validar variants en las options
                if (isset($item['options'])) {
                    foreach ($item['options'] as $optIndex => $option) {
                        $this->validateProductVariant(
                            $validator,
                            $option,
                            "items.{$index}.options.{$optIndex}"
                        );
                    }
                }
            } else {
                // Para items fijos, validar product_id y variant_id
                if (isset($item['product_id'])) {
                    $this->validateProductVariant($validator, $item, "items.{$index}");
                }
            }
        }
    }

    /**
     * Valida que un producto tenga variant_id si tiene variantes
     */
    protected function validateProductVariant($validator, array $data, string $fieldPrefix): void
    {
        $product = \App\Models\Menu\Product::find($data['product_id'] ?? null);

        if (! $product) {
            return;
        }

        // Si el producto tiene variantes, variant_id es requerido
        if ($product->has_variants) {
            if (empty($data['variant_id'])) {
                $validator->errors()->add(
                    "{$fieldPrefix}.variant_id",
                    "Debes seleccionar una variante para {$product->name}."
                );
            } else {
                // Validar que la variante pertenece al producto
                $variantExists = $product->variants()
                    ->where('id', $data['variant_id'])
                    ->exists();

                if (! $variantExists) {
                    $validator->errors()->add(
                        "{$fieldPrefix}.variant_id",
                        "La variante seleccionada no pertenece a {$product->name}."
                    );
                }
            }
        } else {
            // Si el producto NO tiene variantes, variant_id debe ser null
            if (! empty($data['variant_id'])) {
                $validator->errors()->add(
                    "{$fieldPrefix}.variant_id",
                    "{$product->name} no tiene variantes. No debes seleccionar una variante."
                );
            }
        }
    }

    /**
     * Valida que los grupos de elección cumplan con los requisitos
     */
    protected function validateChoiceGroups($validator): void
    {
        foreach ($this->items as $index => $item) {
            $isChoiceGroup = $item['is_choice_group'] ?? false;

            if (! $isChoiceGroup) {
                // Validar que items fijos tengan product_id
                if (empty($item['product_id'])) {
                    $validator->errors()->add(
                        "items.{$index}.product_id",
                        'El producto es obligatorio para items fijos.'
                    );
                }

                continue;
            }

            // Validar que choice groups tengan choice_label
            if (empty($item['choice_label'])) {
                $validator->errors()->add(
                    "items.{$index}.choice_label",
                    'La etiqueta es obligatoria para grupos de elección.'
                );
            }

            // Validar que choice groups tengan options
            if (empty($item['options']) || ! is_array($item['options'])) {
                $validator->errors()->add(
                    "items.{$index}.options",
                    'Un grupo de elección debe tener opciones.'
                );

                continue;
            }

            // Validar mínimo 2 opciones
            if (count($item['options']) < 2) {
                $validator->errors()->add(
                    "items.{$index}.options",
                    'Un grupo de elección debe tener al menos 2 opciones.'
                );
            }

            // Validar duplicados
            $this->validateNoDuplicateOptions($validator, $index, $item['options']);

            // Validar consistencia de variantes
            $this->validateVariantConsistency($validator, $item['options'], $index);
        }
    }

    /**
     * Valida que todas las opciones de un grupo tengan variantes consistentes
     */
    protected function validateVariantConsistency($validator, array $options, int $itemIndex): void
    {
        $variantIds = collect($options)->pluck('variant_id')->filter();

        if ($variantIds->isEmpty()) {
            return;
        }

        // Si algunas opciones tienen variant_id, todas deben tenerlo
        if ($variantIds->count() !== count($options)) {
            $validator->errors()->add(
                "items.{$itemIndex}.options",
                'Todas las opciones deben tener variante o ninguna debe tenerla.'
            );

            return;
        }

        // Validar que todas las variantes sean del mismo tipo (tamaño)
        $variants = \App\Models\Menu\ProductVariant::whereIn('id', $variantIds)->get();
        $variantNames = $variants->pluck('variant_name')->unique();

        if ($variantNames->count() > 1) {
            $validator->errors()->add(
                "items.{$itemIndex}.options",
                "Todas las opciones deben ser de la misma variante ({$variantNames->first()})."
            );
        }
    }

    /**
     * Valida que no haya opciones duplicadas en un grupo
     */
    protected function validateNoDuplicateOptions($validator, int $groupIndex, array $options): void
    {
        $seen = [];

        foreach ($options as $optIndex => $option) {
            $key = ($option['product_id'] ?? 'null').'-'.($option['variant_id'] ?? 'null');

            if (isset($seen[$key])) {
                $validator->errors()->add(
                    "items.{$groupIndex}.options.{$optIndex}",
                    'Esta opción ya existe en el grupo.'
                );
            }

            $seen[$key] = true;
        }
    }
}
