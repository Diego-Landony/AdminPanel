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
            'slug' => 'nullable|string|max:150|unique:promotions,slug,'.$promotionId,
            'description' => 'nullable|string',
            'type' => 'required|in:fixed_price,percentage_discount,two_for_one',

            // Para percentage_discount
            'discount_percentage' => 'required_if:type,percentage_discount|nullable|numeric|min:0|max:100',

            // Validez temporal
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|min:0|max:6',
            'time_from' => 'nullable|date_format:H:i',
            'time_until' => 'nullable|date_format:H:i|after:time_from',

            // Aplicabilidad
            'applies_to' => 'required|in:product,variant,category',
            'min_quantity' => 'integer|min:1',
            'is_active' => 'boolean',

            // Items de promoción
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.category_id' => 'nullable|exists:categories,id',

            // Precios promocionales (solo para fixed_price)
            'items.*.promo_base_price' => 'required_if:type,fixed_price|nullable|numeric|min:0',
            'items.*.promo_delivery_price' => 'required_if:type,fixed_price|nullable|numeric|min:0',
            'items.*.promo_interior_base_price' => 'required_if:type,fixed_price|nullable|numeric|min:0',
            'items.*.promo_interior_delivery_price' => 'required_if:type,fixed_price|nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la promoción es obligatorio.',
            'type.required' => 'El tipo de promoción es obligatorio.',
            'type.in' => 'El tipo de promoción no es válido.',

            'discount_percentage.required_if' => 'El porcentaje de descuento es obligatorio para este tipo de promoción.',
            'discount_percentage.max' => 'El porcentaje no puede ser mayor a 100.',

            'valid_until.after_or_equal' => 'La fecha fin debe ser posterior o igual a la fecha inicio.',
            'time_until.after' => 'La hora fin debe ser posterior a la hora inicio.',

            'applies_to.required' => 'Debes especificar a qué se aplica la promoción.',
            'items.required' => 'Debes agregar al menos un item a la promoción.',
            'items.min' => 'Debes agregar al menos un item a la promoción.',

            'items.*.promo_base_price.required_if' => 'Los precios promocionales son obligatorios para precio fijo.',
        ];
    }

    /**
     * Preparar datos para validación
     */
    protected function prepareForValidation(): void
    {
        // Convertir days_of_week de array a array si viene como string JSON
        if ($this->has('days_of_week') && is_string($this->days_of_week)) {
            $this->merge([
                'days_of_week' => json_decode($this->days_of_week, true),
            ]);
        }
    }
}
