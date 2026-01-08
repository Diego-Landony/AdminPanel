<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para crear una promoción
 */
class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'type' => 'required|in:two_for_one,percentage_discount,daily_special',
            'is_active' => 'boolean',
            'badge_type_id' => 'nullable|exists:badge_types,id',
            'show_badge_on_menu' => 'boolean',

            // Items de promoción
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.variant_id' => [
                'nullable',
                'exists:product_variants,id',
                function ($attribute, $value, $fail) {
                    // Para Sub del Día, validar que no haya conflicto de días con variante
                    if ($this->type === 'daily_special' && $value) {
                        preg_match('/items\.(\d+)\.variant_id/', $attribute, $matches);
                        $index = $matches[1] ?? 0;
                        $weekdays = $this->input("items.{$index}.weekdays");

                        if ($weekdays && count($weekdays) > 0) {
                            $this->validateNoConflictingVariantWeekdays($value, $weekdays, $fail);
                        }
                    }
                },
            ],
            'items.*.category_id' => 'required|exists:categories,id',

            // Para Sub del Día - Campos a nivel de item (4 precios independientes)
            'items.*.special_price_pickup_capital' => 'required_if:type,daily_special|nullable|numeric|min:0',
            'items.*.special_price_delivery_capital' => 'required_if:type,daily_special|nullable|numeric|min:0',
            'items.*.special_price_pickup_interior' => 'required_if:type,daily_special|nullable|numeric|min:0',
            'items.*.special_price_delivery_interior' => 'required_if:type,daily_special|nullable|numeric|min:0',

            // Para Percentage - Campos a nivel de item
            'items.*.discount_percentage' => 'required_if:type,percentage_discount|nullable|numeric|min:1|max:100',

            // Vigencia temporal (2x1, Sub del Día y Percentage)
            'items.*.validity_type' => [
                'required_if:type,daily_special',
                'required_if:type,two_for_one',
                'required_if:type,percentage_discount',
                'nullable',
                'in:permanent,date_range,time_range,date_time_range,weekdays',
            ],

            // Weekdays - SIEMPRE REQUERIDO para daily_special
            'items.*.weekdays' => [
                'required_if:type,daily_special',
                'nullable',
                'array',
                'min:1',
            ],
            'items.*.weekdays.*' => 'integer|min:1|max:7',

            // Fechas y horarios - OPCIONALES (solo se validan si están presentes)
            'items.*.valid_from' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    // Si se proporciona valid_until, valid_from es requerido
                    preg_match('/items\.(\d+)\.valid_from/', $attribute, $matches);
                    $index = $matches[1] ?? 0;
                    $validUntil = $this->input("items.{$index}.valid_until");

                    if ($validUntil && ! $value) {
                        $fail('Si especificas fecha de fin, debes especificar fecha de inicio.');
                    }
                },
            ],
            'items.*.valid_until' => [
                'nullable',
                'date',
                'after_or_equal:items.*.valid_from',
            ],
            'items.*.time_from' => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    // Si se proporciona time_until, time_from es requerido
                    preg_match('/items\.(\d+)\.time_from/', $attribute, $matches);
                    $index = $matches[1] ?? 0;
                    $timeUntil = $this->input("items.{$index}.time_until");

                    if ($timeUntil && ! $value) {
                        $fail('Si especificas hora de fin, debes especificar hora de inicio.');
                    }
                },
            ],
            'items.*.time_until' => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    // Validar que time_until > time_from si ambos están presentes
                    preg_match('/items\.(\d+)\.time_until/', $attribute, $matches);
                    $index = $matches[1] ?? 0;
                    $timeFrom = $this->input("items.{$index}.time_from");

                    if ($timeFrom && $value && $value <= $timeFrom) {
                        $fail('La hora de fin debe ser posterior a la hora de inicio.');
                    }
                },
            ],

        ];
    }

    /**
     * Validar que no haya conflicto de días de la semana para la misma variante
     */
    protected function validateNoConflictingVariantWeekdays(int $variantId, array $weekdays, $fail): void
    {
        if (! $weekdays || count($weekdays) === 0) {
            return;
        }

        // Buscar items existentes de Sub del Día activos para esta variante
        $existingItems = \App\Models\Menu\PromotionItem::whereHas('promotion', function ($q) {
            $q->where('type', 'daily_special')->where('is_active', true);
        })
            ->where('variant_id', $variantId)
            ->get();

        foreach ($existingItems as $item) {
            if ($item->weekdays && count($item->weekdays) > 0) {
                $conflictingDays = array_intersect($weekdays, $item->weekdays);

                if (count($conflictingDays) > 0) {
                    $dayNames = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                    $conflictingDayNames = array_map(fn ($day) => $dayNames[$day], $conflictingDays);
                    $fail('Esta variante ya tiene un Sub del Día activo en: '.implode(', ', $conflictingDayNames));

                    return;
                }
            }
        }
    }

    /**
     * Validación adicional después de las reglas básicas
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateNoDuplicateProductVariantCombinations($validator);
        });
    }

    /**
     * Validar que no haya combinaciones (product_id, variant_id) duplicadas
     */
    protected function validateNoDuplicateProductVariantCombinations($validator): void
    {
        $items = $this->input('items', []);
        $combinations = [];
        $duplicates = [];

        foreach ($items as $index => $item) {
            if (! isset($item['product_id'])) {
                continue;
            }

            $productId = $item['product_id'];
            $variantId = $item['variant_id'] ?? 'null';
            $key = "{$productId}_{$variantId}";

            if (in_array($key, $combinations)) {
                $duplicates[$index] = $key;
            } else {
                $combinations[] = $key;
            }
        }

        if (count($duplicates) > 0) {
            foreach ($duplicates as $index => $key) {
                $validator->errors()->add(
                    "items.{$index}.product_id",
                    'Esta combinación de producto y variante ya existe en otro item de la promoción.'
                );
            }
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la promoción es obligatorio.',
            'type.required' => 'El tipo de promoción es obligatorio.',
            'type.in' => 'El tipo de promoción no es válido.',

            'items.required' => 'Debes agregar al menos un item a la promoción.',
            'items.min' => 'Debes agregar al menos un item a la promoción.',

            // Percentage - Items
            'items.*.discount_percentage.required_if' => 'El porcentaje de descuento es obligatorio.',
            'items.*.discount_percentage.min' => 'El porcentaje debe ser al menos 1%.',
            'items.*.discount_percentage.max' => 'El porcentaje no puede ser mayor a 100%.',

            // Sub del Día y Percentage - Items
            'items.*.product_id.required_if' => 'El producto es obligatorio.',
            'items.*.special_price_pickup_capital.required_if' => 'El precio pickup Capital es obligatorio.',
            'items.*.special_price_delivery_capital.required_if' => 'El precio delivery Capital es obligatorio.',
            'items.*.special_price_pickup_interior.required_if' => 'El precio pickup Interior es obligatorio.',
            'items.*.special_price_delivery_interior.required_if' => 'El precio delivery Interior es obligatorio.',
            'items.*.weekdays.required_if' => 'Debes seleccionar al menos un día de la semana.',
            'items.*.weekdays.min' => 'Debes seleccionar al menos un día de la semana.',
            'items.*.valid_from.date' => 'La fecha de inicio no es válida.',
            'items.*.valid_until.date' => 'La fecha de fin no es válida.',
            'items.*.valid_until.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'items.*.time_from.date_format' => 'El formato de hora de inicio no es válido.',
            'items.*.time_until.date_format' => 'El formato de hora de fin no es válido.',

            // 2x1 - Items
            'items.*.category_id.required_if' => 'La categoría es obligatoria para 2x1.',
            'items.*.category_id.exists' => 'La categoría seleccionada no existe.',
            'items.*.validity_type.required_if' => 'El tipo de vigencia es obligatorio.',
        ];
    }
}
