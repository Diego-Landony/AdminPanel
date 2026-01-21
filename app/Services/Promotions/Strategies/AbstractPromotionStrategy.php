<?php

namespace App\Services\Promotions\Strategies;

use App\Models\Menu\Promotion;
use App\Traits\HasPriceZones;

abstract class AbstractPromotionStrategy implements PromotionStrategyInterface
{
    use HasPriceZones;

    /**
     * Obtiene el promotion_item correspondiente a un cart_item.
     */
    protected function getPromotionItemForCartItem($item, Promotion $promotion)
    {
        if ($item->variant_id) {
            return $promotion->items()
                ->where(function ($q) use ($item) {
                    $q->where('variant_id', $item->variant_id)
                        ->orWhere('product_id', $item->product_id)
                        ->orWhere('category_id', $item->product->category_id);
                })
                ->first();
        }

        return $promotion->items()
            ->where(function ($q) use ($item) {
                $q->where('product_id', $item->product_id)
                    ->orWhere('category_id', $item->product->category_id);
            })
            ->first();
    }
}
