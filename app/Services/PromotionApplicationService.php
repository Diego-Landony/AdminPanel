<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Menu\Promotion;
use App\Services\Promotions\PromotionStrategyResolver;
use App\Traits\HasPriceZones;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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

    protected PromotionStrategyResolver $strategyResolver;

    public function __construct()
    {
        $this->strategyResolver = new PromotionStrategyResolver;
    }

    /**
     * Aplica automáticamente todas las promociones elegibles al carrito
     *
     * @return array Array de promociones aplicadas con su información
     */
    public function applyPromotions(Cart $cart): array
    {
        $appliedPromotions = [];
        $items = $cart->items()->with(['product.category', 'variant', 'combo', 'combinado'])->get();

        if ($items->isEmpty()) {
            return $appliedPromotions;
        }

        $datetime = now();

        foreach ($items as $item) {
            $promotion = null;

            // Saltar combos y combinados (bundle_special) - ya tienen su precio fijo
            if ($item->isCombo() || $item->isCombinado()) {
                continue;
            }

            if ($item->variant_id) {
                $promotion = $this->findActivePromotionForVariant(
                    $item->variant,
                    $datetime
                );
            } elseif ($item->product && $item->product->category) {
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
        $items = $cart->items()->with(['product.category', 'variant', 'combo', 'combinado'])->get();

        if ($items->isEmpty()) {
            return collect();
        }

        $datetime = now();
        $promotionIds = collect();

        foreach ($items as $item) {
            // Saltar combos y combinados (bundle_special) - ya tienen su precio fijo
            if ($item->isCombo() || $item->isCombinado()) {
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
            } elseif ($item->product && $item->product->category) {
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
        $items = $cart->items()->with(['product.category', 'variant', 'combo', 'combinado'])->get();
        $discount = 0;

        if ($promotion->type === 'percentage_discount') {
            foreach ($items as $item) {
                // Saltar combos y combinados (bundle_special)
                if ($item->isCombo() || $item->isCombinado()) {
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
                // Excluir combos y combinados (bundle_special)
                return ! $item->isCombo() && ! $item->isCombinado() && $this->itemQualifiesForPromotion($item, $promotion);
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
     *
     * IMPORTANTE: Verifica weekdays tanto en Promotion como en PromotionItem para
     * asegurar consistencia con la validación posterior en validateAppliedPromotions().
     */
    protected function findActivePromotionForVariant($variant, Carbon $datetime): ?Promotion
    {
        $dayOfWeekIso = $datetime->dayOfWeekIso; // ISO-8601: 1=Lunes, 7=Domingo
        $currentDate = $datetime->toDateString();
        $currentTime = $datetime->format('H:i:s');

        $categoryId = $variant->product->category_id;

        return Promotion::where('is_active', true)
            ->whereHas('items', function ($q) use ($variant, $categoryId, $dayOfWeekIso, $currentDate, $currentTime) {
                // Debe coincidir con variante, producto o categoría
                $q->where(function ($q2) use ($variant, $categoryId) {
                    $q2->where('variant_id', $variant->id)
                        ->orWhere('product_id', $variant->product_id)
                        ->orWhere(function ($q3) use ($categoryId) {
                            if ($categoryId) {
                                $q3->where('category_id', $categoryId);
                            }
                        });
                })
                // Verificar weekdays del item (debe coincidir con día actual si está definido)
                    ->where(function ($q2) use ($dayOfWeekIso) {
                        $q2->whereNull('weekdays')
                            ->orWhereJsonContains('weekdays', $dayOfWeekIso);
                    })
                // Verificar fechas del item
                    ->where(function ($q2) use ($currentDate) {
                        $q2->whereNull('valid_from')
                            ->orWhereDate('valid_from', '<=', $currentDate);
                    })
                    ->where(function ($q2) use ($currentDate) {
                        $q2->whereNull('valid_until')
                            ->orWhereDate('valid_until', '>=', $currentDate);
                    })
                // Verificar horarios del item
                    ->where(function ($q2) use ($currentTime) {
                        $q2->whereNull('time_from')
                            ->orWhereTime('time_from', '<=', $currentTime);
                    })
                    ->where(function ($q2) use ($currentTime) {
                        $q2->whereNull('time_until')
                            ->orWhereTime('time_until', '>=', $currentTime);
                    });
            })
            // También verificar weekdays a nivel de Promotion (si existe)
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
     *
     * IMPORTANTE: Verifica weekdays tanto en Promotion como en PromotionItem para
     * asegurar consistencia con la validación posterior en validateAppliedPromotions().
     */
    protected function findActivePromotionForProduct($product, int $categoryId, Carbon $datetime): ?Promotion
    {
        $dayOfWeekIso = $datetime->dayOfWeekIso; // ISO-8601: 1=Lunes, 7=Domingo
        $currentDate = $datetime->toDateString();
        $currentTime = $datetime->format('H:i:s');

        return Promotion::where('is_active', true)
            ->whereHas('items', function ($q) use ($product, $categoryId, $dayOfWeekIso, $currentDate, $currentTime) {
                $q->where(function ($q2) use ($product, $categoryId) {
                    $q2->where('product_id', $product->id)
                        ->orWhere('category_id', $categoryId);
                })
                // Verificar weekdays del item (debe coincidir con día actual si está definido)
                    ->where(function ($q2) use ($dayOfWeekIso) {
                        $q2->whereNull('weekdays')
                            ->orWhereJsonContains('weekdays', $dayOfWeekIso);
                    })
                // Verificar fechas del item
                    ->where(function ($q2) use ($currentDate) {
                        $q2->whereNull('valid_from')
                            ->orWhereDate('valid_from', '<=', $currentDate);
                    })
                    ->where(function ($q2) use ($currentDate) {
                        $q2->whereNull('valid_until')
                            ->orWhereDate('valid_until', '>=', $currentDate);
                    })
                // Verificar horarios del item
                    ->where(function ($q2) use ($currentTime) {
                        $q2->whereNull('time_from')
                            ->orWhereTime('time_from', '<=', $currentTime);
                    })
                    ->where(function ($q2) use ($currentTime) {
                        $q2->whereNull('time_until')
                            ->orWhereTime('time_until', '>=', $currentTime);
                    });
            })
            // También verificar weekdays a nivel de Promotion (si existe)
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
     * @return array Array indexado por item_id con informacion de descuento
     */
    public function calculateItemDiscounts(Cart $cart): array
    {
        $itemDiscounts = [];
        $items = $cart->items()->with(['product.category', 'variant', 'combo', 'combinado'])->get();

        if ($items->isEmpty()) {
            return $itemDiscounts;
        }

        $datetime = now();
        $dayOfWeek = $datetime->dayOfWeekIso; // ISO-8601: 1=Lunes, ..., 7=Domingo

        // Paso 1: Inicializar descuentos y aplicar Sub del Dia
        $itemDiscounts = $this->initializeItemDiscounts($items, $dayOfWeek);

        // Paso 2: Identificar promociones aplicables por item
        $promotionsMap = $this->buildPromotionsMap($items, $datetime);

        // Paso 3: Calcular descuentos usando estrategias
        $itemDiscounts = $this->applyPromotionStrategies($promotionsMap, $itemDiscounts, $cart, $datetime);

        // Paso 4: Limpiar campos temporales
        foreach ($itemDiscounts as $itemId => $data) {
            unset($itemDiscounts[$itemId]['_daily_special_data']);
        }

        return $itemDiscounts;
    }

    /**
     * Inicializa los descuentos de items y aplica Sub del Dia donde corresponda.
     */
    protected function initializeItemDiscounts(Collection $items, int $dayOfWeek): array
    {
        $itemDiscounts = [];

        foreach ($items as $item) {
            $extrasTotal = $item->getOptionsTotal() * $item->quantity;
            $basePrice = (float) $item->subtotal;

            $itemDiscounts[$item->id] = [
                'discount_amount' => 0.0,
                'original_price' => round($basePrice + $extrasTotal, 2),
                'final_price' => round($basePrice + $extrasTotal, 2),
                'is_daily_special' => false,
                'applied_promotion' => null,
            ];

            // Saltar combos y combinados (bundle_special) - ya tienen su precio fijo
            if ($item->isCombo() || $item->isCombinado()) {
                continue;
            }

            // Verificar si es Sub del Dia y guardar datos para uso posterior
            if ($item->variant_id && $item->variant) {
                $variant = $item->variant;
                if ($variant->is_daily_special && ! empty($variant->daily_special_days)) {
                    if (in_array($dayOfWeek, $variant->daily_special_days)) {
                        $itemDiscounts[$item->id]['is_daily_special'] = true;
                        $this->applyDailySpecialToItem($item, $variant, $extrasTotal, $itemDiscounts);
                    }
                }
            }
        }

        return $itemDiscounts;
    }

    /**
     * Aplica el descuento Sub del Dia a un item especifico.
     */
    protected function applyDailySpecialToItem($item, $variant, float $extrasTotal, array &$itemDiscounts): void
    {
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
            $itemDiscounts[$item->id]['applied_promotion'] = [
                'id' => $dailySpecialPromo?->id,
                'name' => 'Sub del Dia',
                'name_display' => "Sub del Dia -{$discountPercent}%",
                'type' => 'daily_special',
                'value' => 'Q'.number_format($specialPricePerUnit, 2),
                'per_unit_amount' => -round($discountPerUnit, 2),
                'percentage_value' => $discountPercent,
                'show_amount' => true,
            ];

            $itemDiscounts[$item->id]['_daily_special_data'] = [
                'normal_price' => $normalPricePerUnit,
                'special_price' => $specialPricePerUnit,
                'discount_per_unit' => $discountPerUnit,
                'promo_id' => $dailySpecialPromo?->id,
                'discount_percent' => $discountPercent,
            ];
        }
    }

    /**
     * Construye un mapa de promociones aplicables a items.
     *
     * @return array<int, array{promotion: Promotion, items: Collection}>
     */
    protected function buildPromotionsMap(Collection $items, Carbon $datetime): array
    {
        $promotionsMap = [];

        foreach ($items as $item) {
            // Saltar combos y combinados (bundle_special) - ya tienen su precio fijo
            if ($item->isCombo() || $item->isCombinado()) {
                continue;
            }

            $promotion = null;
            if ($item->variant_id) {
                $promotion = $this->findActivePromotionForVariant($item->variant, $datetime);
            } elseif ($item->product && $item->product->category) {
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

        return $promotionsMap;
    }

    /**
     * Aplica estrategias de promocion a los items agrupados.
     */
    protected function applyPromotionStrategies(array $promotionsMap, array $itemDiscounts, Cart $cart, Carbon $datetime): array
    {
        foreach ($promotionsMap as $promoData) {
            $promotion = $promoData['promotion'];
            $promoItems = $promoData['items'];

            $strategy = $this->strategyResolver->resolve($promotion->type);

            if ($strategy) {
                $context = [
                    'itemDiscounts' => $itemDiscounts,
                    'cart' => $cart,
                    'datetime' => $datetime,
                    'dayOfWeek' => $datetime->dayOfWeekIso,
                ];

                $itemDiscounts = $strategy->calculate($promoItems, $promotion, $context);
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

    /**
     * Obtiene las promociones activas desde cache o base de datos
     *
     * El cache tiene un TTL de 5 minutos (300 segundos) para balancear
     * rendimiento con la necesidad de reflejar cambios en promociones.
     */
    protected function getActivePromotions(): Collection
    {
        return Cache::remember('active_promotions', 300, function () {
            return Promotion::query()
                ->active()
                ->with(['items.product', 'items.variant', 'bundleItems.product'])
                ->get();
        });
    }

    /**
     * Invalida el cache de promociones activas
     *
     * Debe llamarse cuando una promoción es creada, actualizada o eliminada.
     */
    public static function clearPromotionsCache(): void
    {
        Cache::forget('active_promotions');
    }
}
