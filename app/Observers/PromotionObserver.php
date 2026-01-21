<?php

namespace App\Observers;

use App\Models\Menu\Promotion;
use App\Services\PromotionApplicationService;

class PromotionObserver
{
    /**
     * Handle the Promotion "created" event.
     */
    public function created(Promotion $promotion): void
    {
        PromotionApplicationService::clearPromotionsCache();
    }

    /**
     * Handle the Promotion "updated" event.
     */
    public function updated(Promotion $promotion): void
    {
        PromotionApplicationService::clearPromotionsCache();
    }

    /**
     * Handle the Promotion "deleted" event.
     */
    public function deleted(Promotion $promotion): void
    {
        PromotionApplicationService::clearPromotionsCache();
    }

    /**
     * Handle the Promotion "restored" event.
     */
    public function restored(Promotion $promotion): void
    {
        PromotionApplicationService::clearPromotionsCache();
    }

    /**
     * Handle the Promotion "force deleted" event.
     */
    public function forceDeleted(Promotion $promotion): void
    {
        PromotionApplicationService::clearPromotionsCache();
    }
}
