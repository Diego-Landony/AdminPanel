<?php

namespace App\Services\Promotions\Strategies;

use App\Models\Menu\Promotion;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TwoForOneStrategy extends AbstractPromotionStrategy
{
    public function canHandle(string $promotionType): bool
    {
        return $promotionType === 'two_for_one';
    }

    public function calculate(Collection $items, Promotion $promotion, array $context): array
    {
        $itemDiscounts = $context['itemDiscounts'] ?? [];
        $datetime = $context['datetime'] ?? now();

        $totalQuantity = $items->sum('quantity');

        if ($totalQuantity < 2) {
            return $itemDiscounts;
        }

        // 2x1 siempre usa precio normal, Sub del Dia solo aplica al sobrante
        // Ejemplo: 3 productos Sub del Dia (Q35 normal, Q22 especial)
        // - 2 productos van al 2x1: pagas Q35 (1 gratis con precio normal)
        // - 1 producto sobrante: Sub del Dia Q22
        // - Total: Q35 + Q22 = Q57

        $sorted = $items->sortBy('unit_price');
        $freeItems = floor($totalQuantity / 2);
        $itemsFor2x1 = $freeItems * 2; // Cantidad que entra en el 2x1

        $freeCount = 0;
        $processedFor2x1 = 0;

        foreach ($sorted as $item) {
            $extrasTotal = $item->getOptionsTotal() * $item->quantity;
            $basePrice = (float) $item->subtotal;
            $dailySpecialData = $itemDiscounts[$item->id]['_daily_special_data'] ?? null;

            // Determinar cuantas unidades de este item van al 2x1
            $quantityAvailableFor2x1 = min($item->quantity, $itemsFor2x1 - $processedFor2x1);
            $quantityForDailySpecial = $item->quantity - $quantityAvailableFor2x1;

            if ($quantityAvailableFor2x1 > 0) {
                $processedFor2x1 += $quantityAvailableFor2x1;

                // Calcular descuento del 2x1 (unidades gratis con precio normal)
                $quantityToDiscount = min($quantityAvailableFor2x1, $freeItems - $freeCount);
                $discount2x1 = $item->unit_price * $quantityToDiscount;
                $freeCount += $quantityToDiscount;

                // Calcular precio de las unidades en 2x1 (precio normal)
                $price2x1Units = $item->unit_price * $quantityAvailableFor2x1;

                // Calcular precio de las unidades sobrantes (Sub del Dia si aplica)
                $priceDailySpecialUnits = 0;
                $discountDailySpecial = 0;
                if ($quantityForDailySpecial > 0 && $dailySpecialData) {
                    $priceDailySpecialUnits = $dailySpecialData['special_price'] * $quantityForDailySpecial;
                    $discountDailySpecial = $dailySpecialData['discount_per_unit'] * $quantityForDailySpecial;
                } elseif ($quantityForDailySpecial > 0) {
                    $priceDailySpecialUnits = $item->unit_price * $quantityForDailySpecial;
                }

                $totalDiscount = $discount2x1 + $discountDailySpecial;
                $originalPrice = $basePrice + $extrasTotal;
                $finalPrice = ($price2x1Units - $discount2x1) + $priceDailySpecialUnits + $extrasTotal;

                $itemDiscounts[$item->id]['discount_amount'] = round($totalDiscount, 2);
                $itemDiscounts[$item->id]['original_price'] = round($originalPrice, 2);
                $itemDiscounts[$item->id]['final_price'] = round($finalPrice, 2);
                $itemDiscounts[$item->id]['is_daily_special'] = false;

                // Mostrar promocion combinada si hay sobrante con Sub del Dia
                if ($quantityForDailySpecial > 0 && $dailySpecialData) {
                    $itemDiscounts[$item->id]['applied_promotion'] = [
                        'id' => $promotion->id,
                        'name' => $promotion->name,
                        'name_display' => "{$promotion->name} 2x1 + Sub del Dia",
                        'type' => 'two_for_one',
                        'value' => '2x1 + Sub del Dia',
                        'per_unit_amount' => null,
                        'percentage_value' => null,
                        'show_amount' => false,
                    ];
                } else {
                    $itemDiscounts[$item->id]['applied_promotion'] = [
                        'id' => $promotion->id,
                        'name' => $promotion->name,
                        'name_display' => "{$promotion->name} 2x1",
                        'type' => $promotion->type,
                        'value' => '2x1',
                        'per_unit_amount' => null,
                        'percentage_value' => null,
                        'show_amount' => false,
                    ];
                }
            } else {
                // Este item completo es sobrante - mantener Sub del Dia si aplica
                if ($dailySpecialData) {
                    // Ya tiene Sub del Dia aplicado, mantenerlo
                } else {
                    // REGLA: Buscar si hay descuento % disponible para este item sobrante
                    $percentagePromo = $this->findPercentageDiscountForItem($item, $datetime);
                    if ($percentagePromo) {
                        $promotionItem = $this->getPromotionItemForCartItem($item, $percentagePromo['promotion']);
                        if ($promotionItem && $promotionItem->discount_percentage) {
                            // Aplicar descuento % al item sobrante
                            $discount = $basePrice * ($promotionItem->discount_percentage / 100);
                            $discountPerUnit = $discount / $item->quantity;
                            $itemDiscounts[$item->id]['discount_amount'] = round($discount, 2);
                            $itemDiscounts[$item->id]['original_price'] = round($basePrice + $extrasTotal, 2);
                            $itemDiscounts[$item->id]['final_price'] = round(($basePrice - $discount) + $extrasTotal, 2);
                            $itemDiscounts[$item->id]['applied_promotion'] = [
                                'id' => $percentagePromo['promotion']->id,
                                'name' => $percentagePromo['promotion']->name,
                                'name_display' => "{$percentagePromo['promotion']->name} -{$promotionItem->discount_percentage}%",
                                'type' => 'percentage_discount',
                                'value' => $promotionItem->discount_percentage.'%',
                                'per_unit_amount' => -round($discountPerUnit, 2),
                                'percentage_value' => (int) $promotionItem->discount_percentage,
                                'show_amount' => true,
                            ];
                        } else {
                            // Sin promocion
                            $itemDiscounts[$item->id]['original_price'] = round($basePrice + $extrasTotal, 2);
                            $itemDiscounts[$item->id]['final_price'] = round($basePrice + $extrasTotal, 2);
                        }
                    } else {
                        // Sin promocion
                        $itemDiscounts[$item->id]['original_price'] = round($basePrice + $extrasTotal, 2);
                        $itemDiscounts[$item->id]['final_price'] = round($basePrice + $extrasTotal, 2);
                    }
                }
            }
        }

        return $itemDiscounts;
    }

    /**
     * Busca promocion de descuento porcentual para un item especifico.
     *
     * Se usa para aplicar descuento % a items sobrantes del 2x1
     *
     * @return array|null Array con ['promotion' => Promotion] o null si no hay
     */
    protected function findPercentageDiscountForItem($item, Carbon $datetime): ?array
    {
        $dayOfWeekIso = $datetime->dayOfWeekIso;
        $currentDate = $datetime->toDateString();
        $currentTime = $datetime->format('H:i:s');

        $query = Promotion::where('is_active', true)
            ->where('type', 'percentage_discount');

        if ($item->variant_id) {
            $query->where(function ($q) use ($item) {
                $q->whereHas('items', fn ($q2) => $q2->where('variant_id', $item->variant_id))
                    ->orWhereHas('items', fn ($q2) => $q2->where('product_id', $item->product_id))
                    ->orWhereHas('items', function ($q2) use ($item) {
                        $categoryId = $item->product->category_id;
                        if ($categoryId) {
                            $q2->where('category_id', $categoryId);
                        }
                    });
            });
        } else {
            $query->whereHas('items', function ($q) use ($item) {
                $q->where(function ($q2) use ($item) {
                    $q2->where('product_id', $item->product_id)
                        ->orWhere('category_id', $item->product->category_id);
                });
            });
        }

        $promotion = $query
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

        if ($promotion) {
            return ['promotion' => $promotion];
        }

        return null;
    }
}
