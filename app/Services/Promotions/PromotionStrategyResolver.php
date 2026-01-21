<?php

namespace App\Services\Promotions;

use App\Services\Promotions\Strategies\BundleSpecialStrategy;
use App\Services\Promotions\Strategies\DailySpecialStrategy;
use App\Services\Promotions\Strategies\PercentageDiscountStrategy;
use App\Services\Promotions\Strategies\PromotionStrategyInterface;
use App\Services\Promotions\Strategies\TwoForOneStrategy;

class PromotionStrategyResolver
{
    /**
     * @var PromotionStrategyInterface[]
     */
    protected array $strategies;

    public function __construct()
    {
        $this->strategies = [
            new TwoForOneStrategy,
            new DailySpecialStrategy,
            new PercentageDiscountStrategy,
            new BundleSpecialStrategy,
        ];
    }

    /**
     * Resuelve la estrategia correcta para un tipo de promocion.
     */
    public function resolve(string $promotionType): ?PromotionStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->canHandle($promotionType)) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * Obtiene todas las estrategias disponibles.
     *
     * @return PromotionStrategyInterface[]
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }
}
