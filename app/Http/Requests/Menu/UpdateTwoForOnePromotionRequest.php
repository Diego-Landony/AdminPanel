<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para actualizar una promoción 2x1
 */
class UpdateTwoForOnePromotionRequest extends FormRequest
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
            'type' => 'required|in:two_for_one',
            'is_active' => 'boolean',

            // Items de promoción - 2x1 solo permite categorías
            'items' => 'required|array|min:1',
            'items.*.category_id' => [
                'required',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($promotionId) {
                    // Validar que no haya conflicto con otra promoción 2x1 activa
                    if ($value) {
                        preg_match('/items\.(\d+)\.category_id/', $attribute, $matches);
                        $index = $matches[1] ?? 0;

                        $validFrom = $this->input("items.{$index}.valid_from");
                        $validUntil = $this->input("items.{$index}.valid_until");
                        $timeFrom = $this->input("items.{$index}.time_from");
                        $timeUntil = $this->input("items.{$index}.time_until");

                        $this->validateNoConflictingCategory(
                            $value,
                            $validFrom,
                            $validUntil,
                            $timeFrom,
                            $timeUntil,
                            $fail,
                            $promotionId
                        );
                    }
                },
            ],

            // Tipo de servicio
            'items.*.service_type' => 'required|in:both,delivery_only,pickup_only',

            // Vigencia temporal
            'items.*.validity_type' => 'required|in:permanent,date_range,time_range,date_time_range',

            // Fechas - opcionales según validity_type
            'items.*.valid_from' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    preg_match('/items\.(\d+)\.valid_from/', $attribute, $matches);
                    $index = $matches[1] ?? 0;
                    $validityType = $this->input("items.{$index}.validity_type");
                    $validUntil = $this->input("items.{$index}.valid_until");

                    // Requerido para date_range y date_time_range
                    if (in_array($validityType, ['date_range', 'date_time_range'])) {
                        if (! $value) {
                            $fail('La fecha de inicio es obligatoria para este tipo de vigencia.');
                        }
                    }

                    if ($validUntil && ! $value) {
                        $fail('Si especificas fecha de fin, debes especificar fecha de inicio.');
                    }
                },
            ],
            'items.*.valid_until' => [
                'nullable',
                'date',
                'after_or_equal:items.*.valid_from',
                function ($attribute, $value, $fail) {
                    preg_match('/items\.(\d+)\.valid_until/', $attribute, $matches);
                    $index = $matches[1] ?? 0;
                    $validityType = $this->input("items.{$index}.validity_type");

                    // Requerido para date_range y date_time_range
                    if (in_array($validityType, ['date_range', 'date_time_range']) && ! $value) {
                        $fail('La fecha de fin es obligatoria para este tipo de vigencia.');
                    }
                },
            ],

            // Horarios - opcionales según validity_type
            'items.*.time_from' => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    preg_match('/items\.(\d+)\.time_from/', $attribute, $matches);
                    $index = $matches[1] ?? 0;
                    $validityType = $this->input("items.{$index}.validity_type");
                    $timeUntil = $this->input("items.{$index}.time_until");

                    // Requerido para time_range y date_time_range
                    if (in_array($validityType, ['time_range', 'date_time_range'])) {
                        if (! $value) {
                            $fail('La hora de inicio es obligatoria para este tipo de vigencia.');
                        }
                    }

                    if ($timeUntil && ! $value) {
                        $fail('Si especificas hora de fin, debes especificar hora de inicio.');
                    }
                },
            ],
            'items.*.time_until' => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    preg_match('/items\.(\d+)\.time_until/', $attribute, $matches);
                    $index = $matches[1] ?? 0;
                    $validityType = $this->input("items.{$index}.validity_type");
                    $timeFrom = $this->input("items.{$index}.time_from");

                    // Requerido para time_range y date_time_range
                    if (in_array($validityType, ['time_range', 'date_time_range']) && ! $value) {
                        $fail('La hora de fin es obligatoria para este tipo de vigencia.');
                    }

                    if ($timeFrom && $value && $value <= $timeFrom) {
                        $fail('La hora de fin debe ser posterior a la hora de inicio.');
                    }
                },
            ],
        ];
    }

    /**
     * Validar que no haya conflicto con otra promoción 2x1 activa para la misma categoría
     */
    protected function validateNoConflictingCategory(
        int $categoryId,
        ?string $validFrom,
        ?string $validUntil,
        ?string $timeFrom,
        ?string $timeUntil,
        $fail,
        ?int $currentPromotionId = null
    ): void {
        // Buscar items existentes de 2x1 activos para esta categoría
        $query = \App\Models\Menu\PromotionItem::whereHas('promotion', function ($q) use ($currentPromotionId) {
            $q->where('type', 'two_for_one')->where('is_active', true);

            // Excluir la promoción actual si estamos editando
            if ($currentPromotionId) {
                $q->where('id', '!=', $currentPromotionId);
            }
        })->where('category_id', $categoryId);

        $existingItems = $query->get();

        if ($existingItems->isEmpty()) {
            return;
        }

        // Si la nueva promoción es permanente, hay conflicto automático
        if (! $validFrom && ! $validUntil && ! $timeFrom && ! $timeUntil) {
            $fail('Ya existe una promoción 2x1 activa para esta categoría.');

            return;
        }

        // Verificar solapamiento de rangos temporales
        foreach ($existingItems as $item) {
            if ($this->hasTemporalOverlap($item, $validFrom, $validUntil, $timeFrom, $timeUntil)) {
                $fail('Ya existe una promoción 2x1 activa para esta categoría que se solapa con el período especificado.');

                return;
            }
        }
    }

    /**
     * Verifica si hay solapamiento temporal entre un item existente y los nuevos valores
     */
    protected function hasTemporalOverlap(
        \App\Models\Menu\PromotionItem $item,
        ?string $newValidFrom,
        ?string $newValidUntil,
        ?string $newTimeFrom,
        ?string $newTimeUntil
    ): bool {
        // Si el item existente es permanente, siempre hay solapamiento
        if ($item->validity_type === 'permanent') {
            return true;
        }

        // Verificar solapamiento de fechas
        if ($item->valid_from && $item->valid_until && $newValidFrom && $newValidUntil) {
            $existingStart = \Carbon\Carbon::parse($item->valid_from);
            $existingEnd = \Carbon\Carbon::parse($item->valid_until);
            $newStart = \Carbon\Carbon::parse($newValidFrom);
            $newEnd = \Carbon\Carbon::parse($newValidUntil);

            $dateOverlap = $newStart->lessThanOrEqualTo($existingEnd) && $newEnd->greaterThanOrEqualTo($existingStart);

            if (! $dateOverlap) {
                return false;
            }
        }

        // Verificar solapamiento de horarios
        if ($item->time_from && $item->time_until && $newTimeFrom && $newTimeUntil) {
            $timeOverlap = $newTimeFrom <= $item->time_until && $newTimeUntil >= $item->time_from;

            if (! $timeOverlap) {
                return false;
            }
        }

        return true;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la promoción es obligatorio.',
            'type.required' => 'El tipo de promoción es obligatorio.',
            'type.in' => 'El tipo de promoción no es válido.',

            'items.required' => 'Debes agregar al menos una categoría a la promoción.',
            'items.min' => 'Debes agregar al menos una categoría a la promoción.',

            'items.*.category_id.required' => 'La categoría es obligatoria.',
            'items.*.category_id.exists' => 'La categoría seleccionada no existe.',

            'items.*.service_type.required' => 'El tipo de servicio es obligatorio.',
            'items.*.service_type.in' => 'El tipo de servicio no es válido.',

            'items.*.validity_type.required' => 'El tipo de vigencia es obligatorio.',
            'items.*.validity_type.in' => 'El tipo de vigencia no es válido.',

            'items.*.valid_from.date' => 'La fecha de inicio no es válida.',
            'items.*.valid_until.date' => 'La fecha de fin no es válida.',
            'items.*.valid_until.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',

            'items.*.time_from.date_format' => 'El formato de hora de inicio no es válido (HH:MM).',
            'items.*.time_until.date_format' => 'El formato de hora de fin no es válido (HH:MM).',
        ];
    }
}
