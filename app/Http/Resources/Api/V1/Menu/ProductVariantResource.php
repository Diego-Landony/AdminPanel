<?php

namespace App\Http\Resources\Api\V1\Menu;

use App\Models\Menu\PromotionItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'size' => $this->size,
            'price' => (float) $this->precio_pickup_capital,
            'prices' => [
                'pickup_capital' => (float) $this->precio_pickup_capital,
                'delivery_capital' => (float) $this->precio_domicilio_capital,
                'pickup_interior' => (float) $this->precio_pickup_interior,
                'delivery_interior' => (float) $this->precio_domicilio_interior,
            ],
            'is_redeemable' => (bool) $this->is_redeemable,
            'points_cost' => $this->points_cost,
            'is_daily_special' => $this->is_daily_special,
            'daily_special_days' => $this->daily_special_days,
            'daily_special_prices' => [
                'pickup_capital' => (float) $this->daily_special_precio_pickup_capital,
                'delivery_capital' => (float) $this->daily_special_precio_domicilio_capital,
                'pickup_interior' => (float) $this->daily_special_precio_pickup_interior,
                'delivery_interior' => (float) $this->daily_special_precio_domicilio_interior,
            ],
            'sort_order' => $this->sort_order,

            // Active promotion (for displaying discounted prices like Amazon/Temu)
            'active_promotion' => $this->when($this->getActivePromotion(), function () {
                $promotion = $this->getActivePromotion();
                $promotionItem = $this->getPromotionItem($promotion);

                return [
                    'id' => $promotion->id,
                    'type' => $promotion->type,
                    'name' => $promotion->name,
                    'discount_percent' => $promotionItem?->discount_percentage,
                    'special_prices' => [
                        'capital' => $promotionItem?->special_price_capital ? (float) $promotionItem->special_price_capital : null,
                        'interior' => $promotionItem?->special_price_interior ? (float) $promotionItem->special_price_interior : null,
                    ],
                    'discounted_prices' => $this->calculateDiscountedPrices($promotionItem),
                    'badge' => $promotion->badgeType ? [
                        'name' => $promotion->badgeType->name,
                        'color' => $promotion->badgeType->color,
                        'text_color' => $promotion->badgeType->text_color,
                    ] : null,
                ];
            }),
        ];
    }

    /**
     * Calculate discounted prices based on promotion item configuration.
     *
     * @return array<string, float|null>|null
     */
    private function calculateDiscountedPrices(?PromotionItem $item): ?array
    {
        if (! $item) {
            return null;
        }

        // If special fixed price is set, use it
        if ($item->special_price_capital || $item->special_price_interior) {
            return [
                'pickup_capital' => $item->special_price_capital ? (float) $item->special_price_capital : null,
                'delivery_capital' => $item->special_price_capital ? (float) $item->special_price_capital : null,
                'pickup_interior' => $item->special_price_interior ? (float) $item->special_price_interior : null,
                'delivery_interior' => $item->special_price_interior ? (float) $item->special_price_interior : null,
            ];
        }

        // If percentage discount is set, calculate discounted prices
        if ($item->discount_percentage) {
            $multiplier = 1 - ($item->discount_percentage / 100);

            return [
                'pickup_capital' => round($this->precio_pickup_capital * $multiplier, 2),
                'delivery_capital' => round($this->precio_domicilio_capital * $multiplier, 2),
                'pickup_interior' => round($this->precio_pickup_interior * $multiplier, 2),
                'delivery_interior' => round($this->precio_domicilio_interior * $multiplier, 2),
            ];
        }

        return null;
    }
}
