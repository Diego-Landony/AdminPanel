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
            'options_price' => (float) $this->options_price,
            'subtotal' => (float) $this->subtotal,
            'selected_options' => $this->formatSelectedOptions($this->selected_options),
            'combo_selections' => $this->combo_selections,
            'notes' => $this->notes,
            'promotion' => $this->when($this->promotion_snapshot, function () {
                return [
                    'name' => $this->promotion_snapshot['name'] ?? null,
                    'type' => $this->promotion_snapshot['type'] ?? null,
                    'discount_amount' => (float) ($this->promotion_snapshot['discount_amount'] ?? 0),
                    'original_price' => (float) ($this->promotion_snapshot['original_price'] ?? $this->subtotal),
                    'final_price' => (float) ($this->promotion_snapshot['final_price'] ?? $this->subtotal),
                    'is_daily_special' => $this->promotion_snapshot['is_daily_special'] ?? false,
                ];
            }),
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
