<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Menu\Promotion;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Servicio de Aplicación de Promociones
 *
 * Maneja la lógica de aplicación de promociones al carrito:
 * - Aplicación automática de promociones elegibles
 * - Aplicación de códigos promocionales
 * - Cálculo de descuentos
 * - Obtención de promociones aplicables
 */
class PromotionApplicationService
{
    /**
     * Aplica automáticamente todas las promociones elegibles al carrito
     *
     * @return array Array de promociones aplicadas con su información
     */
    public function applyPromotions(Cart $cart): array
    {
        $appliedPromotions = [];
        $items = $cart->items()->with(['product.category', 'variant', 'combo'])->get();

        if ($items->isEmpty()) {
            return $appliedPromotions;
        }

        $datetime = now();

        foreach ($items as $item) {
            $promotion = null;

            if ($item->isCombo()) {
                continue;
            }

            if ($item->variant_id) {
                $promotion = $this->findActivePromotionForVariant(
                    $item->variant,
                    $datetime
                );
            } else {
                $promotion = $this->findActivePromotionForProduct(
                    $item->product,
                    $item->product->category->id,
                    $datetime
                );
            }

            if ($promotion) {
                $discount = $this->calculateDiscount($promotion, $cart);

                $appliedPromotions[] = [
                    'promotion_id' => $promotion->id,
                    'promotion_name' => $promotion->name,
                    'promotion_type' => $promotion->type,
                    'discount_amount' => $discount,
                    'item_id' => $item->id,
                ];
            }
        }

        return $appliedPromotions;
    }

    /**
     * Obtiene las promociones aplicables al carrito actual
     */
    public function getApplicablePromotions(Cart $cart): Collection
    {
        $items = $cart->items()->with(['product.category', 'variant', 'combo'])->get();

        if ($items->isEmpty()) {
            return collect();
        }

        $datetime = now();
        $promotionIds = collect();

        foreach ($items as $item) {
            if ($item->isCombo()) {
                continue;
            }

            if ($item->variant_id) {
                $promotion = $this->findActivePromotionForVariant(
                    $item->variant,
                    $datetime
                );

                if ($promotion) {
                    $promotionIds->push($promotion->id);
                }
            } else {
                $promotion = $this->findActivePromotionForProduct(
                    $item->product,
                    $item->product->category->id,
                    $datetime
                );

                if ($promotion) {
                    $promotionIds->push($promotion->id);
                }
            }
        }

        return Promotion::whereIn('id', $promotionIds->unique())
            ->where('is_active', true)
            ->get();
    }

    /**
     * Calcula el monto de descuento para una promoción
     */
    public function calculateDiscount(Promotion $promotion, Cart $cart): float
    {
        $items = $cart->items()->with(['product.category', 'variant', 'combo'])->get();
        $discount = 0;

        if ($promotion->type === 'percentage_discount') {
            foreach ($items as $item) {
                if ($item->isCombo()) {
                    continue;
                }

                $promotionItem = $this->getPromotionItemForCartItem($item, $promotion);

                if ($promotionItem && $promotionItem->discount_percentage) {
                    $itemDiscount = $item->subtotal * ($promotionItem->discount_percentage / 100);
                    $discount += $itemDiscount;
                }
            }
        } elseif ($promotion->type === 'two_for_one') {
            $qualifyingItems = $items->filter(function ($item) use ($promotion) {
                return ! $item->isCombo() && $this->itemQualifiesForPromotion($item, $promotion);
            });

            $totalQuantity = $qualifyingItems->sum('quantity');

            if ($totalQuantity >= 2) {
                $sorted = $qualifyingItems->sortBy('unit_price');
                $freeItems = floor($totalQuantity / 2);
                $freeCount = 0;

                foreach ($sorted as $item) {
                    if ($freeCount < $freeItems) {
                        $quantityToDiscount = min($item->quantity, $freeItems - $freeCount);
                        $discount += $item->unit_price * $quantityToDiscount;
                        $freeCount += $quantityToDiscount;
                    }
                }
            }
        } elseif ($promotion->type === 'bundle_special') {
            $bundlePrice = $this->getBundlePriceForCart($promotion, $cart);

            if ($bundlePrice) {
                $normalPrice = $items->sum('subtotal');
                if ($bundlePrice < $normalPrice) {
                    $discount = $normalPrice - $bundlePrice;
                }
            }
        }

        return round($discount, 2);
    }

    /**
     * Busca promoción activa para una variante
     *
     * Filtra por vigencia: weekdays (ISO-8601: 1=Lunes, 7=Domingo), fechas y horarios.
     * El backend maneja toda la lógica de vigencia para simplificar el trabajo de Flutter.
     */
    protected function findActivePromotionForVariant($variant, Carbon $datetime): ?Promotion
    {
        $dayOfWeekIso = $datetime->dayOfWeekIso; // ISO-8601: 1=Lunes, 7=Domingo
        $currentDate = $datetime->toDateString();
        $currentTime = $datetime->format('H:i:s');

        return Promotion::where('is_active', true)
            ->where(function ($q) use ($variant) {
                $q->whereHas('items', fn ($q2) => $q2->where('variant_id', $variant->id))
                    ->orWhereHas('items', fn ($q2) => $q2->where('product_id', $variant->product_id))
                    ->orWhereHas('items', function ($q2) use ($variant) {
                        $categoryId = $variant->product->category_id;
                        if ($categoryId) {
                            $q2->where('category_id', $categoryId);
                        }
                    });
            })
            ->where(function ($q) use ($dayOfWeekIso) {
                $q->whereNull('weekdays')
                    ->orWhereJsonContains('weekdays', $dayOfWeekIso);
            })
            ->where(function ($q) use ($currentDate) {
                $q->whereNull('valid_from')
                    ->orWhereDate('valid_from', '<=', $currentDate);
            })
            ->where(function ($q) use ($currentDate) {
                $q->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', $currentDate);
            })
            ->where(function ($q) use ($currentTime) {
                $q->whereNull('time_from')
                    ->orWhereTime('time_from', '<=', $currentTime);
            })
            ->where(function ($q) use ($currentTime) {
                $q->whereNull('time_until')
                    ->orWhereTime('time_until', '>=', $currentTime);
            })
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Busca promoción activa para un producto sin variantes
     *
     * Filtra por vigencia: weekdays (ISO-8601: 1=Lunes, 7=Domingo), fechas y horarios.
     * El backend maneja toda la lógica de vigencia para simplificar el trabajo de Flutter.
     */
    protected function findActivePromotionForProduct($product, int $categoryId, Carbon $datetime): ?Promotion
    {
        $dayOfWeekIso = $datetime->dayOfWeekIso; // ISO-8601: 1=Lunes, 7=Domingo
        $currentDate = $datetime->toDateString();
        $currentTime = $datetime->format('H:i:s');

        return Promotion::where('is_active', true)
            ->whereHas('items', function ($q) use ($product, $categoryId) {
                $q->where(function ($q2) use ($product, $categoryId) {
                    $q2->where('product_id', $product->id)
                        ->orWhere('category_id', $categoryId);
                });
            })
            ->where(function ($q) use ($dayOfWeekIso) {
                $q->whereNull('weekdays')
                    ->orWhereJsonContains('weekdays', $dayOfWeekIso);
            })
            ->where(function ($q) use ($currentDate) {
                $q->whereNull('valid_from')
                    ->orWhereDate('valid_from', '<=', $currentDate);
            })
            ->where(function ($q) use ($currentDate) {
                $q->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', $currentDate);
            })
            ->where(function ($q) use ($currentTime) {
                $q->whereNull('time_from')
                    ->orWhereTime('time_from', '<=', $currentTime);
            })
            ->where(function ($q) use ($currentTime) {
                $q->whereNull('time_until')
                    ->orWhereTime('time_until', '>=', $currentTime);
            })
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Verifica si un item califica para una promoción
     */
    protected function itemQualifiesForPromotion($item, Promotion $promotion): bool
    {
        return $this->getPromotionItemForCartItem($item, $promotion) !== null;
    }

    /**
     * Obtiene el promotion_item correspondiente a un cart_item
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

    /**
     * Verifica si una promoción es válida en este momento
     */
    protected function isPromotionValidNow(Promotion $promotion, ?Carbon $datetime = null): bool
    {
        $datetime = $datetime ?? now();

        if (! $promotion->is_active) {
            return false;
        }

        return $promotion->items()->get()->contains(function ($item) use ($datetime) {
            return $item->isValidToday($datetime);
        });
    }

    /**
     * Calcula descuentos detallados por cada item del carrito
     *
     * @return array Array indexado por item_id con información de descuento
     */
    public function calculateItemDiscounts(Cart $cart): array
    {
        $itemDiscounts = [];
        $items = $cart->items()->with(['product.category', 'variant', 'combo'])->get();

        if ($items->isEmpty()) {
            return $itemDiscounts;
        }

        $datetime = now();
        $dayOfWeek = $datetime->dayOfWeek;

        // Primero, identificar todas las promociones aplicables
        $promotionsMap = [];

        foreach ($items as $item) {
            // Inicializar con valores por defecto
            $itemDiscounts[$item->id] = [
                'discount_amount' => 0.0,
                'original_price' => (float) $item->subtotal,
                'final_price' => (float) $item->subtotal,
                'is_daily_special' => false,
                'applied_promotion' => null,
            ];

            if ($item->isCombo()) {
                continue;
            }

            // Verificar si es Sub del Día
            if ($item->variant_id && $item->variant) {
                $variant = $item->variant;
                if ($variant->is_daily_special && ! empty($variant->daily_special_days)) {
                    if (in_array($dayOfWeek, $variant->daily_special_days)) {
                        $itemDiscounts[$item->id]['is_daily_special'] = true;

                        // El precio ya está calculado con el precio especial
                        continue; // Sub del día no acumula con otras promociones
                    }
                }
            }

            // Buscar promoción aplicable
            $promotion = null;
            if ($item->variant_id) {
                $promotion = $this->findActivePromotionForVariant($item->variant, $datetime);
            } else {
                $promotion = $this->findActivePromotionForProduct(
                    $item->product,
                    $item->product->category->id,
                    $datetime
                );
            }

            if ($promotion) {
                if (! isset($promotionsMap[$promotion->id])) {
                    $promotionsMap[$promotion->id] = [
                        'promotion' => $promotion,
                        'items' => collect(),
                    ];
                }
                $promotionsMap[$promotion->id]['items']->push($item);
            }
        }

        // Calcular descuentos por tipo de promoción
        foreach ($promotionsMap as $promoData) {
            $promotion = $promoData['promotion'];
            $promoItems = $promoData['items'];

            if ($promotion->type === 'percentage_discount') {
                foreach ($promoItems as $item) {
                    $promotionItem = $this->getPromotionItemForCartItem($item, $promotion);
                    if ($promotionItem && $promotionItem->discount_percentage) {
                        $discount = $item->subtotal * ($promotionItem->discount_percentage / 100);
                        $itemDiscounts[$item->id]['discount_amount'] = round($discount, 2);
                        $itemDiscounts[$item->id]['final_price'] = round($item->subtotal - $discount, 2);
                        $itemDiscounts[$item->id]['applied_promotion'] = [
                            'id' => $promotion->id,
                            'name' => $promotion->name,
                            'type' => $promotion->type,
                            'value' => $promotionItem->discount_percentage.'%',
                        ];
                    }
                }
            } elseif ($promotion->type === 'two_for_one') {
                $totalQuantity = $promoItems->sum('quantity');

                if ($totalQuantity >= 2) {
                    $sorted = $promoItems->sortBy('unit_price');
                    $freeItems = floor($totalQuantity / 2);
                    $freeCount = 0;

                    foreach ($sorted as $item) {
                        if ($freeCount < $freeItems) {
                            $quantityToDiscount = min($item->quantity, $freeItems - $freeCount);
                            $discount = $item->unit_price * $quantityToDiscount;
                            $freeCount += $quantityToDiscount;

                            $itemDiscounts[$item->id]['discount_amount'] = round($discount, 2);
                            $itemDiscounts[$item->id]['final_price'] = round($item->subtotal - $discount, 2);
                            $itemDiscounts[$item->id]['applied_promotion'] = [
                                'id' => $promotion->id,
                                'name' => $promotion->name,
                                'type' => $promotion->type,
                                'value' => '2x1',
                            ];
                        }
                    }
                }
            } elseif ($promotion->type === 'bundle_special') {
                $bundlePrice = $this->getBundlePriceForCart($promotion, $cart);

                if ($bundlePrice) {
                    $normalTotal = $promoItems->sum('subtotal');
                    if ($bundlePrice < $normalTotal) {
                        $totalDiscount = $normalTotal - $bundlePrice;
                        // Distribuir el descuento proporcionalmente
                        foreach ($promoItems as $item) {
                            $proportion = $item->subtotal / $normalTotal;
                            $discount = $totalDiscount * $proportion;

                            $itemDiscounts[$item->id]['discount_amount'] = round($discount, 2);
                            $itemDiscounts[$item->id]['final_price'] = round($item->subtotal - $discount, 2);
                            $itemDiscounts[$item->id]['applied_promotion'] = [
                                'id' => $promotion->id,
                                'name' => $promotion->name,
                                'type' => $promotion->type,
                                'value' => 'Q'.number_format($bundlePrice, 2),
                            ];
                        }
                    }
                }
            }
        }

        return $itemDiscounts;
    }

    /**
     * Obtiene el precio del bundle según zona y tipo de servicio del carrito
     */
    protected function getBundlePriceForCart(Promotion $promotion, Cart $cart): ?float
    {
        $zone = $cart->zone ?? 'capital';
        $serviceType = $cart->service_type ?? 'pickup';

        return $promotion->getPriceForZoneCombinado($zone, $serviceType);
    }
}
