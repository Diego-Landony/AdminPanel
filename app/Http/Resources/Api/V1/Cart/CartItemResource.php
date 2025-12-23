<?php

namespace App\Http\Resources\Api\V1\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isProduct = $this->product_id !== null;
        $isCombo = $this->combo_id !== null;

        return [
            'id' => $this->id,
            'type' => $isProduct ? 'product' : 'combo',
            'product' => $this->when($isProduct && $this->relationLoaded('product'), function () {
                $product = $this->product;
                $variant = $this->relationLoaded('variant') ? $this->variant : null;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image_url' => $product->image,
                    'variant' => $variant ? [
                        'id' => $variant->id,
                        'name' => $variant->name,
                    ] : null,
                ];
            }),
            'combo' => $this->when($isCombo && $this->relationLoaded('combo'), function () {
                $combo = $this->combo;

                return [
                    'id' => $combo->id,
                    'name' => $combo->name,
                    'image_url' => $combo->image,
                ];
            }),
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'subtotal' => (float) $this->subtotal,
            // Campos de descuento para mostrar precio tachado en Flutter
            'discount_amount' => $this->discount_info['discount_amount'] ?? 0.0,
            'final_price' => $this->discount_info['final_price'] ?? (float) $this->subtotal,
            'is_daily_special' => $this->discount_info['is_daily_special'] ?? false,
            'applied_promotion' => $this->discount_info['applied_promotion'] ?? null,
            'selected_options' => $this->selected_options ? collect($this->selected_options)->map(function ($option) {
                return [
                    'section_id' => $option['section_id'] ?? null,
                    'option_id' => $option['option_id'] ?? null,
                    'name' => $option['name'] ?? null,
                    'price' => isset($option['price']) ? (float) $option['price'] : 0,
                ];
            })->toArray() : [],
            'combo_selections' => $this->combo_selections,
            'options_total' => $this->when(method_exists($this->resource, 'getOptionsTotal'), function () {
                return (float) $this->getOptionsTotal();
            }),
            'line_total' => $this->when(method_exists($this->resource, 'getLineTotal'), function () {
                return (float) $this->getLineTotal();
            }),
            'notes' => $this->notes,
        ];
    }
}
