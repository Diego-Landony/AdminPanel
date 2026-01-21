<?php

namespace App\Services\Promotions\Strategies;

use App\Models\Menu\Promotion;
use Illuminate\Support\Collection;

interface PromotionStrategyInterface
{
    /**
     * Determina si esta estrategia puede manejar el tipo de promocion dado.
     */
    public function canHandle(string $promotionType): bool;

    /**
     * Calcula los descuentos para los items de la promocion.
     *
     * @param  Collection  $items  Items del carrito que califican para la promocion
     * @param  Promotion  $promotion  La promocion a aplicar
     * @param  array  $context  Contexto adicional (itemDiscounts, datetime, etc.)
     * @return array Array con descuentos calculados indexados por item_id
     */
    public function calculate(Collection $items, Promotion $promotion, array $context): array;
}
