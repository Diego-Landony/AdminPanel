<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\CustomerPointsTransaction;
use App\Models\PointsSetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireInactivePoints implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     * Expire points based on the configured expiration method.
     */
    public function handle(): void
    {
        $settings = PointsSetting::get();

        if ($settings->usesTotalExpiration()) {
            $this->expireTotal($settings);
        } else {
            $this->expireFifo($settings);
        }
    }

    /**
     * Metodo TOTAL: Si hay X meses sin actividad, todos los puntos expiran
     */
    private function expireTotal(PointsSetting $settings): void
    {
        $inactiveDate = now()->subMonths($settings->expiration_months);

        $customers = Customer::query()
            ->where('points', '>', 0)
            ->where(function ($query) use ($inactiveDate) {
                $query->where('points_last_activity_at', '<', $inactiveDate)
                    ->orWhereNull('points_last_activity_at');
            })
            ->get();

        $expiredCount = 0;
        $totalPointsExpired = 0;

        foreach ($customers as $customer) {
            try {
                DB::transaction(function () use ($customer, $settings, &$totalPointsExpired) {
                    $pointsToExpire = $customer->points;

                    CustomerPointsTransaction::create([
                        'customer_id' => $customer->id,
                        'points' => -$pointsToExpire,
                        'type' => 'expired',
                        'description' => "Puntos expirados por {$settings->expiration_months} meses de inactividad",
                        'expires_at' => now(),
                        'is_expired' => true,
                    ]);

                    // Marcar todas las transacciones earned como expiradas
                    CustomerPointsTransaction::where('customer_id', $customer->id)
                        ->where('type', 'earned')
                        ->where('is_expired', false)
                        ->update(['is_expired' => true]);

                    $customer->update([
                        'points' => 0,
                        'points_updated_at' => now(),
                    ]);

                    $this->updateCustomerType($customer);

                    $totalPointsExpired += $pointsToExpire;
                });

                $expiredCount++;
            } catch (\Exception $e) {
                Log::error("Error expiring points for customer {$customer->id}: {$e->getMessage()}");
            }
        }

        Log::info("ExpireInactivePoints (TOTAL): Expired {$totalPointsExpired} points from {$expiredCount} customers");
    }

    /**
     * Metodo FIFO: Solo expiran los puntos mas antiguos que tengan X meses
     */
    private function expireFifo(PointsSetting $settings): void
    {
        $inactiveDate = now()->subMonths($settings->expiration_months);

        // Solo procesar clientes sin actividad reciente
        $customers = Customer::query()
            ->where('points', '>', 0)
            ->where(function ($query) use ($inactiveDate) {
                $query->where('points_last_activity_at', '<', $inactiveDate)
                    ->orWhereNull('points_last_activity_at');
            })
            ->get();

        $expiredCount = 0;
        $totalPointsExpired = 0;

        foreach ($customers as $customer) {
            try {
                DB::transaction(function () use ($customer, &$totalPointsExpired, &$expiredCount) {
                    // Buscar transacciones earned no expiradas cuya fecha de expiracion ya paso
                    $expiredTransactions = CustomerPointsTransaction::query()
                        ->where('customer_id', $customer->id)
                        ->where('type', 'earned')
                        ->where('is_expired', false)
                        ->where('expires_at', '<', now())
                        ->orderBy('created_at', 'asc')
                        ->get();

                    $totalExpiredForCustomer = 0;

                    foreach ($expiredTransactions as $transaction) {
                        $transaction->update(['is_expired' => true]);
                        $totalExpiredForCustomer += $transaction->points;
                    }

                    if ($totalExpiredForCustomer > 0) {
                        CustomerPointsTransaction::create([
                            'customer_id' => $customer->id,
                            'points' => -$totalExpiredForCustomer,
                            'type' => 'expired',
                            'description' => "Puntos expirados (FIFO) - {$expiredTransactions->count()} transacciones",
                            'is_expired' => true,
                        ]);

                        $customer->decrement('points', $totalExpiredForCustomer);
                        $customer->update(['points_updated_at' => now()]);

                        $this->updateCustomerType($customer);

                        $totalPointsExpired += $totalExpiredForCustomer;
                        $expiredCount++;
                    }
                });
            } catch (\Exception $e) {
                Log::error("Error expiring points (FIFO) for customer {$customer->id}: {$e->getMessage()}");
            }
        }

        Log::info("ExpireInactivePoints (FIFO): Expired {$totalPointsExpired} points from {$expiredCount} customers");
    }

    /**
     * Actualiza el tipo de cliente basado en sus puntos actuales
     */
    private function updateCustomerType(Customer $customer): void
    {
        if (method_exists($customer, 'updateCustomerType')) {
            $customer->updateCustomerType();
        }
    }
}
