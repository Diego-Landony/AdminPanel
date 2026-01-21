<?php

namespace App\Services\Promotions\Strategies;

use App\Models\Menu\Promotion;
use Illuminate\Support\Collection;

class PercentageDiscountStrategy extends AbstractPromotionStrategy
{
    public function canHandle(string $promotionType): bool
    {
        return $promotionType === 'percentage_discount';
    }

    public function calculate(Collection $items, Promotion $promotion, array $context): array
    {
        $itemDiscounts = $context['itemDiscounts'] ?? [];

        foreach ($items as $item) {
            // REGLA: Sub del Dia tiene prioridad sobre Descuento %
            // Si el item ya tiene Sub del Dia aplicado, NO aplicar descuento %
            if ($itemDiscounts[$item->id]['is_daily_special'] ?? false) {
                continue;
            }

            $promotionItem = $this->getPromotionItemForCartItem($item, $promotion);
            if ($promotionItem && $promotionItem->discount_percentage) {
                $extrasTotal = $item->getOptionsTotal() * $item->quantity;
                $basePrice = (float) $item->subtotal;

                // Descuento solo sobre precio base
                $discount = $basePrice * ($promotionItem->discount_percentage / 100);

                $itemDiscounts[$item->id]['discount_amount'] = round($discount, 2);
                $itemDiscounts[$item->id]['original_price'] = round($basePrice + $extrasTotal, 2);
                $itemDiscounts[$item->id]['final_price'] = round(($basePrice - $discount) + $extrasTotal, 2);
                $itemDiscounts[$item->id]['applied_promotion'] = [
                    'id' => $promotion->id,
                    'name' => $promotion->name,
                    'name_display' => "{$promotion->name} -{$promotionItem->discount_percentage}%",
                    'type' => $promotion->type,
                    'value' => $promotionItem->discount_percentage.'%',
                ];
            }
        }

        return $itemDiscounts;
    }
}
