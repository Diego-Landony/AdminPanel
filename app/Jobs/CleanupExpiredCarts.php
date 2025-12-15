<?php

namespace App\Jobs;

use App\Models\Cart;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CleanupExpiredCarts implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     * Mark expired carts as abandoned and cleanup old abandoned carts.
     */
    public function handle(): void
    {
        // Mark expired carts as abandoned
        $expiredCount = Cart::query()
            ->where('status', 'active')
            ->where('expires_at', '<', now())
            ->update([
                'status' => 'abandoned',
                'updated_at' => now(),
            ]);

        // Delete cart items for carts abandoned more than 30 days ago
        $thirtyDaysAgo = now()->subDays(30);

        $oldCarts = Cart::query()
            ->where('status', 'abandoned')
            ->where('updated_at', '<', $thirtyDaysAgo)
            ->get();

        $deletedItemsCount = 0;
        foreach ($oldCarts as $cart) {
            $deletedItemsCount += $cart->items()->count();
            $cart->items()->delete();
        }

        Log::info("CleanupExpiredCarts: Marked {$expiredCount} carts as abandoned, deleted {$deletedItemsCount} items from old abandoned carts");
    }
}
