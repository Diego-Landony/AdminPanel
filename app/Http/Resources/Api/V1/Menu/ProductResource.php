<?php

namespace App\Http\Resources\Api\V1\Menu;

use App\Models\Menu\PromotionItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasVariants = (bool) $this->has_variants;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->getImageUrl(),
            'category_id' => $this->category_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category->name),
            'has_variants' => $hasVariants,

            // Solo incluir precios si NO tiene variantes
            // Si tiene variantes, los precios vienen en cada variante
            'price' => $this->when(! $hasVariants, (float) $this->precio_pickup_capital),
            'prices' => $this->when(! $hasVariants, [
                'pickup_capital' => (float) $this->precio_pickup_capital,
                'delivery_capital' => (float) $this->precio_domicilio_capital,
                'pickup_interior' => (float) $this->precio_pickup_interior,
                'delivery_interior' => (float) $this->precio_domicilio_interior,
            ]),

            'sort_order' => $this->sort_order,

            // Redemption by points (solo para productos sin variantes)
            'is_redeemable' => $this->when(! $hasVariants, (bool) $this->is_redeemable),
            'points_cost' => $this->when(! $hasVariants && $this->is_redeemable, $this->points_cost),

            // Relationships
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'sections' => SectionResource::collection($this->whenLoaded('sections')),
            'badges' => BadgeResource::collection($this->whenLoaded('activeBadges')),

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
                        'pickup_capital' => $promotionItem?->special_price_pickup_capital ? (float) $promotionItem->special_price_pickup_capital : null,
                        'delivery_capital' => $promotionItem?->special_price_delivery_capital ? (float) $promotionItem->special_price_delivery_capital : null,
                        'pickup_interior' => $promotionItem?->special_price_pickup_interior ? (float) $promotionItem->special_price_pickup_interior : null,
                        'delivery_interior' => $promotionItem?->special_price_delivery_interior ? (float) $promotionItem->special_price_delivery_interior : null,
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

        // If special fixed prices are set, use them directly (4 independent prices)
        if ($item->special_price_pickup_capital || $item->special_price_delivery_capital ||
            $item->special_price_pickup_interior || $item->special_price_delivery_interior) {
            return [
                'pickup_capital' => $item->special_price_pickup_capital ? (float) $item->special_price_pickup_capital : null,
                'delivery_capital' => $item->special_price_delivery_capital ? (float) $item->special_price_delivery_capital : null,
                'pickup_interior' => $item->special_price_pickup_interior ? (float) $item->special_price_pickup_interior : null,
                'delivery_interior' => $item->special_price_delivery_interior ? (float) $item->special_price_delivery_interior : null,
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
