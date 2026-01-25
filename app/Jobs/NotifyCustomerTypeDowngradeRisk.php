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
 * - Verifica si el cliente bajaría de nivel en X días
 * - Envía notificaciones escalonadas: 7 días, 3 días y 1 día antes
 * - Se ejecuta diariamente
 */
class NotifyCustomerTypeDowngradeRisk implements ShouldQueue
{
    use Queueable;

    /**
     * Días en los que se envían advertencias
     *
     * @var array<int>
     */
    protected array $warningDays = [7, 3, 1];

    /**
     * Meses de la ventana de cálculo
     */
    protected int $windowMonths = 6;

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
                    $daysUntilDowngrade = $this->calculateDaysUntilDowngrade($customer, $customerTypes, $defaultType);

                    if ($daysUntilDowngrade !== null && $this->shouldNotifyToday($customer, $daysUntilDowngrade)) {
                        $riskData = $this->getRiskData($customer, $daysUntilDowngrade, $customerTypes, $defaultType);
                        $this->sendWarningNotification($customer, $riskData, $daysUntilDowngrade);
                        $notified++;
                    }
                }
            });

        Log::info('NotifyCustomerTypeDowngradeRisk: Completado', [
            'customers_notified' => $notified,
        ]);
    }

    /**
     * Calcula en cuántos días el cliente bajaría de nivel
     */
    protected function calculateDaysUntilDowngrade(Customer $customer, $customerTypes, CustomerType $defaultType): ?int
    {
        // Buscar el día exacto en que bajaría de nivel (dentro de los próximos 7 días)
        for ($days = 1; $days <= 7; $days++) {
            $projectedPoints = $this->getProjectedPoints($customer->id, $days);
            $projectedType = $this->determineType($projectedPoints, $customerTypes, $defaultType);

            // Si bajaría de nivel ese día
            if ($projectedType->points_required < $customer->customerType->points_required) {
                return $days;
            }
        }

        return null; // No bajaría en los próximos 7 días
    }

    /**
     * Determina si se debe notificar hoy basado en los días de advertencia configurados
     */
    protected function shouldNotifyToday(Customer $customer, int $daysUntilDowngrade): bool
    {
        // Si los días hasta el downgrade coinciden con alguno de nuestros días de advertencia
        if (! in_array($daysUntilDowngrade, $this->warningDays)) {
            return false;
        }

        // Verificar que no se haya enviado ya esta advertencia específica
        // (el mismo día de advertencia en la última semana)
        if ($customer->downgrade_warning_sent_at) {
            $lastWarningDaysAgo = $customer->downgrade_warning_sent_at->diffInDays(now());

            // Si la última notificación fue hace menos días que la diferencia entre advertencias
            // (evita duplicados del mismo nivel de advertencia)
            if ($lastWarningDaysAgo < 2) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtiene los puntos proyectados para X días en el futuro
     */
    protected function getProjectedPoints(int $customerId, int $daysInFuture): int
    {
        $futureStart = Carbon::now()->addDays($daysInFuture)->subMonths($this->windowMonths)->startOfDay();
        $futureEnd = Carbon::now()->addDays($daysInFuture)->endOfDay();

        return (int) DB::table('customer_points_transactions')
            ->where('customer_id', $customerId)
            ->where('points', '>', 0)
            ->whereBetween('created_at', [$futureStart, $futureEnd])
            ->sum('points');
    }

    /**
     * Determina el tipo de cliente basado en los puntos
     */
    protected function determineType(int $points, $customerTypes, CustomerType $defaultType): CustomerType
    {
        foreach ($customerTypes as $type) {
            if ($points >= $type->points_required) {
                return $type;
            }
        }

        return $defaultType;
    }

    /**
     * Obtiene los datos de riesgo para la notificación
     *
     * @return array{current_points: int, projected_points: int, projected_type: CustomerType, points_needed: int}
     */
    protected function getRiskData(Customer $customer, int $daysUntilDowngrade, $customerTypes, CustomerType $defaultType): array
    {
        $currentStart = Carbon::now()->subMonths($this->windowMonths)->startOfDay();
        $currentEnd = Carbon::now()->endOfDay();

        $currentPoints = (int) DB::table('customer_points_transactions')
            ->where('customer_id', $customer->id)
            ->where('points', '>', 0)
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->sum('points');

        $projectedPoints = $this->getProjectedPoints($customer->id, $daysUntilDowngrade);
        $projectedType = $this->determineType($projectedPoints, $customerTypes, $defaultType);

        $pointsNeeded = max(0, $customer->customerType->points_required - $projectedPoints);

        return [
            'current_points' => $currentPoints,
            'projected_points' => $projectedPoints,
            'projected_type' => $projectedType,
            'points_needed' => $pointsNeeded,
        ];
    }

    /**
     * Envía la notificación de advertencia
     */
    protected function sendWarningNotification(Customer $customer, array $riskData, int $daysUntilDowngrade): void
    {
        try {
            $customer->notify(new CustomerTypeDowngradeWarningNotification(
                $customer->customerType,
                $riskData['projected_type'],
                $riskData['current_points'],
                $riskData['points_needed'],
                $daysUntilDowngrade
            ));

            // Marcar que se envió la notificación
            $customer->update(['downgrade_warning_sent_at' => now()]);

            Log::info('NotifyCustomerTypeDowngradeRisk: Notificación enviada', [
                'customer_id' => $customer->id,
                'current_type' => $customer->customerType->name,
                'projected_type' => $riskData['projected_type']->name,
                'days_until_downgrade' => $daysUntilDowngrade,
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
