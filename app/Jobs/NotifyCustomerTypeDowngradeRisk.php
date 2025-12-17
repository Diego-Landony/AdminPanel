<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\CustomerType;
use App\Notifications\CustomerTypeDowngradeWarningNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job para notificar a clientes en riesgo de bajar de nivel.
 *
 * Lógica:
 * - Simula cómo quedaría el cliente en 1 semana (cuando puntos viejos salgan de la ventana)
 * - Si el cliente bajaría de nivel, envía notificación de advertencia
 * - Se ejecuta semanalmente para dar tiempo al cliente de reaccionar
 */
class NotifyCustomerTypeDowngradeRisk implements ShouldQueue
{
    use Queueable;

    /**
     * Días de anticipación para la advertencia
     */
    protected int $warningDays = 7;

    /**
     * Meses de la ventana de cálculo
     */
    protected int $windowMonths = 6;

    public function __construct(?int $warningDays = null)
    {
        if ($warningDays !== null) {
            $this->warningDays = $warningDays;
        }
    }

    public function handle(): void
    {
        Log::info('NotifyCustomerTypeDowngradeRisk: Iniciando detección de clientes en riesgo');

        $customerTypes = CustomerType::active()
            ->orderBy('points_required', 'desc')
            ->get();

        $defaultType = CustomerType::getDefault();

        if (! $defaultType || $customerTypes->isEmpty()) {
            Log::warning('NotifyCustomerTypeDowngradeRisk: No hay tipos de cliente configurados');

            return;
        }

        $notified = 0;

        // Obtener clientes con tipo asignado (no Regular/default)
        Customer::query()
            ->whereNotNull('customer_type_id')
            ->where('customer_type_id', '!=', $defaultType->id)
            ->with('customerType')
            ->chunk(500, function ($customers) use ($customerTypes, $defaultType, &$notified) {
                foreach ($customers as $customer) {
                    $result = $this->checkCustomerRisk($customer, $customerTypes, $defaultType);

                    if ($result['at_risk']) {
                        $this->sendWarningNotification($customer, $result);
                        $notified++;
                    }
                }
            });

        Log::info('NotifyCustomerTypeDowngradeRisk: Completado', [
            'customers_notified' => $notified,
        ]);
    }

    /**
     * Verifica si un cliente está en riesgo de bajar de nivel
     *
     * @return array{at_risk: bool, current_points: int, projected_points: int, projected_type: ?CustomerType, points_needed: int}
     */
    protected function checkCustomerRisk(Customer $customer, $customerTypes, CustomerType $defaultType): array
    {
        // Ventana actual (últimos 6 meses)
        $currentStart = Carbon::now()->subMonths($this->windowMonths)->startOfDay();
        $currentEnd = Carbon::now()->endOfDay();

        // Ventana futura (como se vería en X días)
        $futureStart = Carbon::now()->addDays($this->warningDays)->subMonths($this->windowMonths)->startOfDay();
        $futureEnd = Carbon::now()->addDays($this->warningDays)->endOfDay();

        // Puntos actuales en la ventana
        $currentPoints = (int) DB::table('customer_points_transactions')
            ->where('customer_id', $customer->id)
            ->where('points', '>', 0)
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->sum('points');

        // Puntos proyectados (qué puntos quedarán en la ventana en X días)
        $projectedPoints = (int) DB::table('customer_points_transactions')
            ->where('customer_id', $customer->id)
            ->where('points', '>', 0)
            ->whereBetween('created_at', [$futureStart, $futureEnd])
            ->sum('points');

        // Determinar el tipo proyectado
        $projectedType = $defaultType;
        foreach ($customerTypes as $type) {
            if ($projectedPoints >= $type->points_required) {
                $projectedType = $type;
                break;
            }
        }

        // ¿Bajaría de nivel?
        $atRisk = $customer->customer_type_id !== null &&
                  $projectedType->id !== $customer->customer_type_id &&
                  $projectedType->points_required < $customer->customerType->points_required;

        // Calcular puntos necesarios para mantener nivel actual
        $pointsNeeded = 0;
        if ($atRisk && $customer->customerType) {
            $pointsNeeded = max(0, $customer->customerType->points_required - $projectedPoints);
        }

        return [
            'at_risk' => $atRisk,
            'current_points' => $currentPoints,
            'projected_points' => $projectedPoints,
            'projected_type' => $projectedType,
            'points_needed' => $pointsNeeded,
        ];
    }

    /**
     * Envía la notificación de advertencia
     */
    protected function sendWarningNotification(Customer $customer, array $riskData): void
    {
        try {
            $customer->notify(new CustomerTypeDowngradeWarningNotification(
                $customer->customerType,
                $riskData['projected_type'],
                $riskData['current_points'],
                $riskData['points_needed']
            ));

            Log::info('NotifyCustomerTypeDowngradeRisk: Notificación enviada', [
                'customer_id' => $customer->id,
                'current_type' => $customer->customerType->name,
                'projected_type' => $riskData['projected_type']->name,
                'points_needed' => $riskData['points_needed'],
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyCustomerTypeDowngradeRisk: Error enviando notificación', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
