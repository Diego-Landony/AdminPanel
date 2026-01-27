<?php

namespace App\Http\Resources\Api\V1\Order;

use App\Traits\FormatsSelectedOptions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    use FormatsSelectedOptions;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isCombo = $this->combo_id !== null;
        $snapshot = $this->product_snapshot;
        $promoSnapshot = $this->promotion_snapshot;

        // Calcular bundle_savings desde options_breakdown
        $bundleSavings = isset($snapshot['options_breakdown']['bundle_discount'])
            ? (float) $snapshot['options_breakdown']['bundle_discount']
            : 0.0;

        // Calcular precios con promoción
        $originalPrice = (float) ($promoSnapshot['original_price'] ?? $this->subtotal);
        $discountAmount = (float) ($promoSnapshot['discount_amount'] ?? 0);
        $finalPrice = (float) ($promoSnapshot['final_price'] ?? $this->subtotal);

        return [
            'id' => $this->id,
            'type' => $isCombo ? 'combo' : 'product',
            'product' => $this->when(! $isCombo && $snapshot, function () use ($snapshot) {
                return [
                    'id' => $snapshot['product_id'] ?? null,
                    'name' => $snapshot['name'] ?? null,
                    'category_name' => $snapshot['category'] ?? null,
                    'image_url' => $this->getProductImageUrl(),
                    'variant' => isset($snapshot['variant_id']) ? [
                        'id' => $snapshot['variant_id'],
                        'name' => $snapshot['variant'] ?? null,
                    ] : null,
                ];
            }),
            'combo' => $this->when($isCombo && $snapshot, function () use ($snapshot) {
                return [
                    'id' => $snapshot['combo_id'] ?? null,
                    'name' => $snapshot['name'] ?? null,
                    'image_url' => $this->getComboImageUrl(),
                    'items' => $snapshot['items'] ?? [],
                ];
            }),
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'options_total' => (float) $this->options_price,
            'options_breakdown' => $this->when(
                isset($snapshot['options_breakdown']),
                fn () => [
                    'items_total' => (float) ($snapshot['options_breakdown']['items_total'] ?? 0),
                    'bundle_discount' => (float) ($snapshot['options_breakdown']['bundle_discount'] ?? 0),
                    'final' => (float) ($snapshot['options_breakdown']['final'] ?? $this->options_price),
                ]
            ),
            'bundle_savings' => $bundleSavings,
            'subtotal' => (float) $this->subtotal,
            'original_price' => $originalPrice,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'is_daily_special' => $promoSnapshot['is_daily_special'] ?? false,
            'applied_promotion' => $this->when($promoSnapshot, function () use ($promoSnapshot) {
                return [
                    'id' => $promoSnapshot['id'] ?? null,
                    'name' => $promoSnapshot['name'] ?? null,
                    'type' => $promoSnapshot['type'] ?? null,
                    'value' => $promoSnapshot['value'] ?? null,
                ];
            }),
            'selected_options' => $this->formatSelectedOptions($this->selected_options),
            'combo_selections' => $this->formatComboSelections($this->combo_selections),
            'notes' => $this->notes,
        ];
    }

    /**
     * Get product image URL from the product model.
     */
    protected function getProductImageUrl(): ?string
    {
        if ($this->relationLoaded('product') && $this->product) {
            return $this->product->getImageUrl();
        }

        return null;
    }

    /**
     * Get combo image URL from the combo model.
     */
    protected function getComboImageUrl(): ?string
    {
        if ($this->relationLoaded('combo') && $this->combo) {
            return $this->combo->getImageUrl();
        }

        return null;
    }
}
