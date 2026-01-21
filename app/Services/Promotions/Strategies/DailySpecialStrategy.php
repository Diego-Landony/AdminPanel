<?php

namespace App\Services\Promotions\Strategies;

use App\Models\Menu\Promotion;
use Illuminate\Support\Collection;

class DailySpecialStrategy extends AbstractPromotionStrategy
{
    public function canHandle(string $promotionType): bool
    {
        return $promotionType === 'daily_special';
    }

    /**
     * Aplica el Sub del Dia a los items elegibles.
     *
     * Este metodo NO se usa directamente en calculateItemDiscounts ya que
     * el Sub del Dia se aplica en un paso previo. Sin embargo, se mantiene
     * para consistencia de la interfaz y posible uso futuro.
     */
    public function calculate(Collection $items, Promotion $promotion, array $context): array
    {
        $itemDiscounts = $context['itemDiscounts'] ?? [];
        $dayOfWeek = $context['dayOfWeek'] ?? now()->dayOfWeekIso;

        foreach ($items as $item) {
            if (! $item->variant_id || ! $item->variant) {
                continue;
            }

            $variant = $item->variant;
            if (! $variant->is_daily_special || empty($variant->daily_special_days)) {
                continue;
            }

            if (! in_array($dayOfWeek, $variant->daily_special_days)) {
                continue;
            }

            $extrasTotal = $item->getOptionsTotal() * $item->quantity;
            $cartModel = $item->cart;
            $zone = $cartModel->zone ?? 'capital';
            $serviceType = $cartModel->service_type ?? 'pickup';
            $priceField = $this->getPriceField($zone, $serviceType);
            $dailySpecialPriceField = 'daily_special_'.$priceField;

            $normalPricePerUnit = (float) ($variant->{$priceField} ?? 0);
            $specialPricePerUnit = (float) ($variant->{$dailySpecialPriceField} ?? $normalPricePerUnit);

            if ($specialPricePerUnit < $normalPricePerUnit) {
                $discountPerUnit = $normalPricePerUnit - $specialPricePerUnit;
                $totalDiscount = $discountPerUnit * $item->quantity;

                $dailySpecialPromo = Promotion::where('type', 'daily_special')
                    ->where('is_active', true)
                    ->first();

                $discountPercent = round((($normalPricePerUnit - $specialPricePerUnit) / $normalPricePerUnit) * 100);

                $itemDiscounts[$item->id]['discount_amount'] = round($totalDiscount, 2);
                $itemDiscounts[$item->id]['original_price'] = round(($normalPricePerUnit * $item->quantity) + $extrasTotal, 2);
                $itemDiscounts[$item->id]['final_price'] = round(($specialPricePerUnit * $item->quantity) + $extrasTotal, 2);
                $itemDiscounts[$item->id]['is_daily_special'] = true;
                $itemDiscounts[$item->id]['applied_promotion'] = [
                    'id' => $dailySpecialPromo?->id,
                    'name' => 'Sub del Dia',
                    'name_display' => "Sub del Dia -{$discountPercent}%",
                    'type' => 'daily_special',
                    'value' => 'Q'.number_format($specialPricePerUnit, 2),
                ];

                // Guardar datos del Sub del Dia para el calculo hibrido con 2x1
                $itemDiscounts[$item->id]['_daily_special_data'] = [
                    'normal_price' => $normalPricePerUnit,
                    'special_price' => $specialPricePerUnit,
                    'discount_per_unit' => $discountPerUnit,
                    'promo_id' => $dailySpecialPromo?->id,
                    'discount_percent' => $discountPercent,
                ];
            }
        }

        return $itemDiscounts;
    }
}
