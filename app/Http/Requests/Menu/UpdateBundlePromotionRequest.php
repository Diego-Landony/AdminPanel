<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBundlePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $promotionId = $this->route('bundle_special') ? $this->route('bundle_special')->id : $this->route('promotion')?->id;

        return [
            // Información básica
            'name' => ['required', 'string', 'max:255', 'unique:promotions,name,'.$promotionId],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'type' => ['required', 'string', 'in:bundle_special'],

            // Precios especiales (solo 2: capital e interior)
            'special_bundle_price_capital' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'special_bundle_price_interior' => ['required', 'numeric', 'min:0', 'max:9999.99'],

            // Tipo de vigencia
            'validity_type' => ['required', 'string', 'in:permanent,date_range,time_range,date_time_range'],

            // Vigencia temporal (sin validación after_or_equal:today en valid_from porque puede estar en el pasado)
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'time_from' => ['nullable', 'date_format:H:i'],
            'time_until' => ['nullable', 'date_format:H:i', 'after:time_from'],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'min:1', 'max:7'], // 1=Monday, 7=Sunday

            // Items del combo (mínimo 2)
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
            // Nombre
            'name.required' => 'El nombre del combinado es obligatorio.',
            'name.unique' => 'Ya existe una promoción con este nombre.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',

            // Descripción
            'description.max' => 'La descripción no puede tener más de 500 caracteres.',

            // Precios
            'special_bundle_price_capital.required' => 'El precio para zona capital es obligatorio.',
            'special_bundle_price_capital.min' => 'El precio debe ser mayor o igual a 0.',
            'special_bundle_price_interior.required' => 'El precio para zona interior es obligatorio.',
            'special_bundle_price_interior.min' => 'El precio debe ser mayor o igual a 0.',

            // Vigencia
            'valid_from.date' => 'La fecha de inicio debe ser una fecha válida.',
            'valid_until.date' => 'La fecha de fin debe ser una fecha válida.',
            'valid_until.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'time_from.date_format' => 'La hora de inicio debe tener el formato HH:MM.',
            'time_until.date_format' => 'La hora de fin debe tener el formato HH:MM.',
            'time_until.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'weekdays.array' => 'Los días de la semana deben ser un arreglo.',
            'weekdays.*.integer' => 'Los días de la semana deben ser valores entre 1 y 7.',
            'weekdays.*.min' => 'Los días de la semana deben ser valores entre 1 y 7.',
            'weekdays.*.max' => 'Los días de la semana deben ser valores entre 1 y 7.',

            // Items
            'items.required' => 'Debes agregar al menos 2 productos al combinado.',
            'items.min' => 'Un combinado debe tener al menos 2 productos.',
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

    protected function prepareForValidation(): void
    {
        // Type siempre será 'bundle_special'
        $this->merge([
            'type' => 'bundle_special',
            'is_active' => $this->boolean('is_active', true),
        ]);

        // Convertir campos temporales vacíos a null
        $temporalFields = ['valid_from', 'valid_until', 'time_from', 'time_until', 'weekdays'];
        $cleanedData = [];

        foreach ($temporalFields as $field) {
            if ($this->has($field) && ($this->$field === '' || empty($this->$field))) {
                $cleanedData[$field] = null;
            }
        }

        // Si es permanente, limpiar todos los campos temporales
        if ($this->validity_type === 'permanent') {
            $cleanedData['valid_from'] = null;
            $cleanedData['valid_until'] = null;
            $cleanedData['time_from'] = null;
            $cleanedData['time_until'] = null;
        }
        // Si es solo date_range, limpiar horarios
        elseif ($this->validity_type === 'date_range') {
            $cleanedData['time_from'] = null;
            $cleanedData['time_until'] = null;
        }
        // Si es solo time_range, limpiar fechas
        elseif ($this->validity_type === 'time_range') {
            $cleanedData['valid_from'] = null;
            $cleanedData['valid_until'] = null;
        }

        if (! empty($cleanedData)) {
            $this->merge($cleanedData);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->items) {
                // Validaciones igual que combos
                $this->validateActiveProducts($validator);
                $this->validateVariantRequirements($validator);
                $this->validateChoiceGroups($validator);
            }

            // Validar lógica de vigencia
            $this->validateTemporalLogic($validator);
        });
    }

    /**
     * Valida que todos los productos del combinado estén activos
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
        $sizes = $variants->pluck('size')->unique();

        if ($sizes->count() > 1) {
            $validator->errors()->add(
                "items.{$itemIndex}.options",
                "Todas las opciones deben ser del mismo tamaño ({$sizes->first()})."
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

    /**
     * Valida la lógica de vigencia temporal
     */
    protected function validateTemporalLogic($validator): void
    {
        $validityType = $this->validity_type;

        // Validar que los campos requeridos estén presentes según validity_type
        if ($validityType === 'date_range' || $validityType === 'date_time_range') {
            if (empty($this->valid_from)) {
                $validator->errors()->add('valid_from', 'La fecha de inicio es obligatoria.');
            }
            if (empty($this->valid_until)) {
                $validator->errors()->add('valid_until', 'La fecha de fin es obligatoria.');
            }
        }

        if ($validityType === 'time_range' || $validityType === 'date_time_range') {
            if (empty($this->time_from)) {
                $validator->errors()->add('time_from', 'La hora de inicio es obligatoria.');
            }
            if (empty($this->time_until)) {
                $validator->errors()->add('time_until', 'La hora de fin es obligatoria.');
            }
        }

        // Nota: La validación de weekdays vacío se maneja en prepareForValidation()
        // que convierte [] a null (todos los días)
    }
}
