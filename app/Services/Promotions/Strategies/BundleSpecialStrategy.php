<?php

namespace App\Services\Promotions\Strategies;

use App\Models\Cart;
use App\Models\Menu\Promotion;
use Illuminate\Support\Collection;

class BundleSpecialStrategy extends AbstractPromotionStrategy
{
    public function canHandle(string $promotionType): bool
    {
        return $promotionType === 'bundle_special';
    }

    public function calculate(Collection $items, Promotion $promotion, array $context): array
    {
        $itemDiscounts = $context['itemDiscounts'] ?? [];
        $cart = $context['cart'] ?? null;

        if (! $cart) {
            return $itemDiscounts;
        }

        $bundlePrice = $this->getBundlePriceForCart($promotion, $cart);

        if (! $bundlePrice) {
            return $itemDiscounts;
        }

        $normalTotal = $items->sum('subtotal');
        if ($bundlePrice >= $normalTotal) {
            return $itemDiscounts;
        }

        $totalDiscount = $normalTotal - $bundlePrice;

        // Distribuir el descuento proporcionalmente
        foreach ($items as $item) {
            $extrasTotal = $item->getOptionsTotal() * $item->quantity;
            $basePrice = (float) $item->subtotal;
            $proportion = $basePrice / $normalTotal;
            $discount = $totalDiscount * $proportion;

            $itemDiscounts[$item->id]['discount_amount'] = round($discount, 2);
            $itemDiscounts[$item->id]['original_price'] = round($basePrice + $extrasTotal, 2);
            $itemDiscounts[$item->id]['final_price'] = round(($basePrice - $discount) + $extrasTotal, 2);

            // Calcular porcentaje de ahorro para el bundle
            $bundleSavingsPercent = round((($normalTotal - $bundlePrice) / $normalTotal) * 100);
            $itemDiscounts[$item->id]['applied_promotion'] = [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'name_display' => "{$promotion->name} (Ahorra {$bundleSavingsPercent}%)",
                'type' => $promotion->type,
                'value' => 'Q'.number_format($bundlePrice, 2),
            ];
        }

        return $itemDiscounts;
    }

    /**
     * Obtiene el precio del bundle segun zona y tipo de servicio del carrito.
     */
    protected function getBundlePriceForCart(Promotion $promotion, Cart $cart): ?float
    {
        $zone = $cart->zone ?? 'capital';
        $serviceType = $cart->service_type ?? 'pickup';

        return $promotion->getPriceForZoneCombinado($zone, $serviceType);
    }
}
