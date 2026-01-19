<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Menu\Promotion;
use App\Traits\HasPriceZones;
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
    use HasPriceZones;

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
     * IMPORTANTE: Los descuentos se aplican SOLO al precio base del producto.
     * Los extras (opciones adicionales) SIEMPRE se cobran al 100%.
     *
     * - original_price = (precio_base * cantidad) + (extras * cantidad)
     * - discount_amount = descuento calculado sobre precio base
     * - final_price = (precio_base_con_descuento * cantidad) + (extras * cantidad)
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
        $dayOfWeek = $datetime->dayOfWeekIso; // ISO-8601: 1=Lunes, ..., 7=Domingo

        // Primero, identificar todas las promociones aplicables
        $promotionsMap = [];

        foreach ($items as $item) {
            // Calcular total de extras (se obtiene desde DB usando getOptionsTotal)
            $extrasTotal = $item->getOptionsTotal() * $item->quantity;
            $basePrice = (float) $item->subtotal; // precio_base * cantidad

            // Inicializar con valores por defecto (incluyendo extras)
            $itemDiscounts[$item->id] = [
                'discount_amount' => 0.0,
                'original_price' => round($basePrice + $extrasTotal, 2),
                'final_price' => round($basePrice + $extrasTotal, 2),
                'is_daily_special' => false,
                'applied_promotion' => null,
            ];

            if ($item->isCombo()) {
                continue;
            }

            // Verificar si es Sub del Día y guardar datos para uso posterior
            // El Sub del Día se aplicará inicialmente, pero puede ser reemplazado por 2x1
            if ($item->variant_id && $item->variant) {
                $variant = $item->variant;
                if ($variant->is_daily_special && ! empty($variant->daily_special_days)) {
                    if (in_array($dayOfWeek, $variant->daily_special_days)) {
                        $itemDiscounts[$item->id]['is_daily_special'] = true;

                        // Calcular datos del Sub del Día
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

                            // Aplicar Sub del Día inicialmente (puede ser sobrescrito por 2x1)
                            $itemDiscounts[$item->id]['discount_amount'] = round($totalDiscount, 2);
                            $itemDiscounts[$item->id]['original_price'] = round(($normalPricePerUnit * $item->quantity) + $extrasTotal, 2);
                            $itemDiscounts[$item->id]['final_price'] = round(($specialPricePerUnit * $item->quantity) + $extrasTotal, 2);
                            $itemDiscounts[$item->id]['applied_promotion'] = [
                                'id' => $dailySpecialPromo?->id,
                                'name' => 'Sub del Día',
                                'name_display' => "Sub del Día -{$discountPercent}%",
                                'type' => 'daily_special',
                                'value' => 'Q'.number_format($specialPricePerUnit, 2),
                            ];

                            // Guardar datos del Sub del Día para el cálculo híbrido con 2x1
                            $itemDiscounts[$item->id]['_daily_special_data'] = [
                                'normal_price' => $normalPricePerUnit,
                                'special_price' => $specialPricePerUnit,
                                'discount_per_unit' => $discountPerUnit,
                                'promo_id' => $dailySpecialPromo?->id,
                                'discount_percent' => $discountPercent,
                            ];
                        }

                        // NO hacer continue - permitir que el item participe en 2x1
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
                    // REGLA: Sub del Día tiene prioridad sobre Descuento %
                    // Si el item ya tiene Sub del Día aplicado, NO aplicar descuento %
                    if ($itemDiscounts[$item->id]['is_daily_special']) {
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
            } elseif ($promotion->type === 'two_for_one') {
                $totalQuantity = $promoItems->sum('quantity');

                if ($totalQuantity >= 2) {
                    // 2x1 siempre usa precio normal, Sub del Día solo aplica al sobrante
                    // Ejemplo: 3 productos Sub del Día (Q35 normal, Q22 especial)
                    // - 2 productos van al 2x1: pagas Q35 (1 gratis con precio normal)
                    // - 1 producto sobrante: Sub del Día Q22
                    // - Total: Q35 + Q22 = Q57

                    $sorted = $promoItems->sortBy('unit_price');
                    $freeItems = floor($totalQuantity / 2);
                    $itemsFor2x1 = $freeItems * 2; // Cantidad que entra en el 2x1
                    $leftoverItems = $totalQuantity - $itemsFor2x1; // Sobrantes para Sub del Día

                    $freeCount = 0;
                    $processedFor2x1 = 0;

                    foreach ($sorted as $item) {
                        $extrasTotal = $item->getOptionsTotal() * $item->quantity;
                        $basePrice = (float) $item->subtotal;
                        $dailySpecialData = $itemDiscounts[$item->id]['_daily_special_data'] ?? null;

                        // Determinar cuántas unidades de este item van al 2x1
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

                            // Calcular precio de las unidades sobrantes (Sub del Día si aplica)
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

                            // Mostrar promoción combinada si hay sobrante con Sub del Día
                            if ($quantityForDailySpecial > 0 && $dailySpecialData) {
                                $itemDiscounts[$item->id]['applied_promotion'] = [
                                    'id' => $promotion->id,
                                    'name' => $promotion->name,
                                    'name_display' => "{$promotion->name} 2x1 + Sub del Día",
                                    'type' => 'two_for_one',
                                    'value' => '2x1 + Sub del Día',
                                ];
                            } else {
                                $itemDiscounts[$item->id]['applied_promotion'] = [
                                    'id' => $promotion->id,
                                    'name' => $promotion->name,
                                    'name_display' => "{$promotion->name} 2x1",
                                    'type' => $promotion->type,
                                    'value' => '2x1',
                                ];
                            }
                        } else {
                            // Este item completo es sobrante - mantener Sub del Día si aplica
                            if ($dailySpecialData) {
                                // Ya tiene Sub del Día aplicado, mantenerlo
                            } else {
                                // REGLA: Buscar si hay descuento % disponible para este item sobrante
                                $percentagePromo = $this->findPercentageDiscountForItem($item, $datetime);
                                if ($percentagePromo) {
                                    $promotionItem = $this->getPromotionItemForCartItem($item, $percentagePromo['promotion']);
                                    if ($promotionItem && $promotionItem->discount_percentage) {
                                        // Aplicar descuento % al item sobrante
                                        $discount = $basePrice * ($promotionItem->discount_percentage / 100);
                                        $itemDiscounts[$item->id]['discount_amount'] = round($discount, 2);
                                        $itemDiscounts[$item->id]['original_price'] = round($basePrice + $extrasTotal, 2);
                                        $itemDiscounts[$item->id]['final_price'] = round(($basePrice - $discount) + $extrasTotal, 2);
                                        $itemDiscounts[$item->id]['applied_promotion'] = [
                                            'id' => $percentagePromo['promotion']->id,
                                            'name' => $percentagePromo['promotion']->name,
                                            'name_display' => "{$percentagePromo['promotion']->name} -{$promotionItem->discount_percentage}%",
                                            'type' => 'percentage_discount',
                                            'value' => $promotionItem->discount_percentage.'%',
                                        ];
                                    } else {
                                        // Sin promoción
                                        $itemDiscounts[$item->id]['original_price'] = round($basePrice + $extrasTotal, 2);
                                        $itemDiscounts[$item->id]['final_price'] = round($basePrice + $extrasTotal, 2);
                                    }
                                } else {
                                    // Sin promoción
                                    $itemDiscounts[$item->id]['original_price'] = round($basePrice + $extrasTotal, 2);
                                    $itemDiscounts[$item->id]['final_price'] = round($basePrice + $extrasTotal, 2);
                                }
                            }
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
                    }
                }
            }
        }

        // Limpiar campos temporales
        foreach ($itemDiscounts as $itemId => $data) {
            unset($itemDiscounts[$itemId]['_daily_special_data']);
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

    /**
     * Busca promoción de descuento porcentual para un item específico
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
