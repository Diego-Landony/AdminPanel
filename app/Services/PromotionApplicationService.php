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
            if ($promotion->special_bundle_price_capital || $promotion->special_bundle_price_interior) {
                $normalPrice = $items->sum('subtotal');
                $bundlePrice = $cart->zone === 'capital'
                    ? $promotion->special_bundle_price_capital
                    : $promotion->special_bundle_price_interior;

                if ($bundlePrice && $bundlePrice < $normalPrice) {
                    $discount = $normalPrice - $bundlePrice;
                }
            }
        }

        return round($discount, 2);
    }

    /**
     * Busca promoción activa para una variante
     */
    protected function findActivePromotionForVariant($variant, Carbon $datetime): ?Promotion
    {
        $dayOfWeek = $datetime->dayOfWeek;
        $currentDate = $datetime->toDateString();
        $currentTime = $datetime->format('H:i:s');

        return Promotion::where('is_active', true)
            ->where(function ($q) use ($variant) {
                $q->whereHas('items', fn ($q2) => $q2->where('variant_id', $variant->id))
                    ->orWhereHas('items', fn ($q2) => $q2->where('product_id', $variant->product_id))
                    ->orWhereHas('items', function ($q2) use ($variant) {
                        $categoryIds = $variant->product->categories()->pluck('categories.id');
                        $q2->whereIn('category_id', $categoryIds);
                    });
            })
            ->where(function ($q) use ($dayOfWeek) {
                $q->whereNull('weekdays')
                    ->orWhereJsonContains('weekdays', $dayOfWeek);
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
     */
    protected function findActivePromotionForProduct($product, int $categoryId, Carbon $datetime): ?Promotion
    {
        $dayOfWeek = $datetime->dayOfWeek;
        $currentDate = $datetime->toDateString();
        $currentTime = $datetime->format('H:i:s');

        return Promotion::where('is_active', true)
            ->whereHas('items', function ($q) use ($product, $categoryId) {
                $q->where(function ($q2) use ($product, $categoryId) {
                    $q2->where('product_id', $product->id)
                        ->orWhere('category_id', $categoryId);
                });
            })
            ->where(function ($q) use ($dayOfWeek) {
                $q->whereNull('weekdays')
                    ->orWhereJsonContains('weekdays', $dayOfWeek);
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
                        ->orWhereIn('category_id', $item->product->categories()->pluck('categories.id'));
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
}
