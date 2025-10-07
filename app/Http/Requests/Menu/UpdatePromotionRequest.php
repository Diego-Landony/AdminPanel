<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para actualizar una promoción
 */
class UpdatePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $promotionId = $this->route('promotion')->id ?? null;

        return [
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'type' => 'required|in:fixed_price,percentage_discount,two_for_one,daily_special',
            'is_active' => 'boolean',

            // Para percentage_discount
            'discount_percentage' => 'required_if:type,percentage_discount|nullable|numeric|min:0|max:100',

            // Aplicabilidad
            'applies_to' => 'required_unless:type,daily_special|nullable|in:product,variant,category',
            'min_quantity' => 'integer|min:1',

            // Items de promoción
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required_if:type,daily_special',
                'nullable',
                'exists:products,id',
                function ($attribute, $value, $fail) use ($promotionId) {
                    // Para Sub del Día, validar que no haya conflicto de días
                    if ($this->type === 'daily_special' && $value) {
                        // Extraer índice del item
                        preg_match('/items\.(\d+)\.product_id/', $attribute, $matches);
                        $index = $matches[1] ?? 0;
                        $weekdays = $this->input("items.{$index}.weekdays");

                        if ($weekdays && count($weekdays) > 0) {
                            $this->validateNoConflictingWeekdays($value, $weekdays, $fail, $promotionId);
                        }
                    }
                },
            ],
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.category_id' => 'nullable|exists:categories,id',

            // Para Sub del Día - Campos a nivel de item
            'items.*.special_price_capital' => 'required_if:type,daily_special|nullable|numeric|min:0',
            'items.*.special_price_interior' => 'required_if:type,daily_special|nullable|numeric|min:0',
            'items.*.service_type' => 'required_if:type,daily_special|nullable|in:both,delivery_only,pickup_only',

            // Vigencia temporal
            'items.*.validity_type' => 'required_if:type,daily_special|nullable|in:permanent,date_range,time_range,date_time_range,weekdays',

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

            // Precios promocionales (solo para fixed_price)
            'items.*.promo_base_price' => 'required_if:type,fixed_price|nullable|numeric|min:0',
            'items.*.promo_delivery_price' => 'required_if:type,fixed_price|nullable|numeric|min:0',
            'items.*.promo_interior_base_price' => 'required_if:type,fixed_price|nullable|numeric|min:0',
            'items.*.promo_interior_delivery_price' => 'required_if:type,fixed_price|nullable|numeric|min:0',
        ];
    }

    /**
     * Validar que no haya conflicto de días de la semana para el mismo producto
     */
    protected function validateNoConflictingWeekdays(int $productId, array $weekdays, $fail, ?int $currentPromotionId = null): void
    {
        if (! $weekdays || count($weekdays) === 0) {
            return;
        }

        // Buscar items existentes de Sub del Día activos para este producto
        $query = \App\Models\Menu\PromotionItem::whereHas('promotion', function ($q) use ($currentPromotionId) {
            $q->where('type', 'daily_special')->where('is_active', true);

            // Excluir la promoción actual si estamos editando
            if ($currentPromotionId) {
                $q->where('id', '!=', $currentPromotionId);
            }
        })->where('product_id', $productId);

        $existingItems = $query->get();

        foreach ($existingItems as $item) {
            if ($item->weekdays && count($item->weekdays) > 0) {
                $conflictingDays = array_intersect($weekdays, $item->weekdays);

                if (count($conflictingDays) > 0) {
                    $dayNames = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                    $conflictingDayNames = array_map(fn ($day) => $dayNames[$day], $conflictingDays);
                    $fail('Este producto ya tiene un Sub del Día activo en: '.implode(', ', $conflictingDayNames));

                    return;
                }
            }
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la promoción es obligatorio.',
            'type.required' => 'El tipo de promoción es obligatorio.',
            'type.in' => 'El tipo de promoción no es válido.',

            // Percentage
            'discount_percentage.required_if' => 'El porcentaje de descuento es obligatorio para este tipo de promoción.',
            'discount_percentage.max' => 'El porcentaje no puede ser mayor a 100.',

            'applies_to.required_unless' => 'Debes especificar a qué se aplica la promoción.',
            'items.required' => 'Debes agregar al menos un item a la promoción.',
            'items.min' => 'Debes agregar al menos un item a la promoción.',

            // Sub del Día - Items
            'items.*.product_id.required_if' => 'El producto es obligatorio para Sub del Día.',
            'items.*.special_price_capital.required_if' => 'El precio especial para Capital es obligatorio.',
            'items.*.special_price_interior.required_if' => 'El precio especial para Interior es obligatorio.',
            'items.*.service_type.required_if' => 'El tipo de servicio es obligatorio.',
            'items.*.weekdays.required_if' => 'Debes seleccionar al menos un día de la semana.',
            'items.*.weekdays.min' => 'Debes seleccionar al menos un día de la semana.',
            'items.*.valid_from.date' => 'La fecha de inicio no es válida.',
            'items.*.valid_until.date' => 'La fecha de fin no es válida.',
            'items.*.valid_until.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'items.*.time_from.date_format' => 'El formato de hora de inicio no es válido.',
            'items.*.time_until.date_format' => 'El formato de hora de fin no es válido.',

            'items.*.promo_base_price.required_if' => 'Los precios promocionales son obligatorios para precio fijo.',
        ];
    }
}
