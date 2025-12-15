<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\CustomerPointsTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireInactivePoints implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     * Expire points for customers with no activity in the last 6 months.
     */
    public function handle(): void
    {
        $sixMonthsAgo = now()->subMonths(6);

        $customers = Customer::query()
            ->where('points', '>', 0)
            ->where(function ($query) use ($sixMonthsAgo) {
                $query->where('points_last_activity_at', '<', $sixMonthsAgo)
                    ->orWhereNull('points_last_activity_at');
            })
            ->get();

        $expiredCount = 0;
        $totalPointsExpired = 0;

        foreach ($customers as $customer) {
            try {
                DB::transaction(function () use ($customer, &$totalPointsExpired) {
                    $pointsToExpire = $customer->points;

                    CustomerPointsTransaction::create([
                        'customer_id' => $customer->id,
                        'points' => -$pointsToExpire,
                        'type' => 'expired',
                        'description' => 'Puntos expirados por inactividad de 6 meses',
                    ]);

                    $customer->points = 0;
                    $customer->points_updated_at = now();
                    $customer->save();

                    $customer->updateCustomerType();

                    $totalPointsExpired += $pointsToExpire;
                });

                $expiredCount++;
            } catch (\Exception $e) {
                Log::error("Error expiring points for customer {$customer->id}: {$e->getMessage()}");
            }
        }

        Log::info("ExpireInactivePoints: Expired {$totalPointsExpired} points from {$expiredCount} customers");
    }
}
