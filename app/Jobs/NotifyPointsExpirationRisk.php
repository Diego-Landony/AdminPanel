<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\PointsSetting;
use App\Notifications\PointsExpirationWarningNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job para notificar a clientes con puntos próximos a vencer.
 *
 * Lógica:
 * - Verifica si el cliente tiene puntos que vencerán en X días por inactividad
 * - Envía notificaciones escalonadas: 7 días, 3 días y 1 día antes
 * - Se ejecuta diariamente
 */
class NotifyPointsExpirationRisk implements ShouldQueue
{
    use Queueable;

    /**
     * Días en los que se envían advertencias
     *
     * @var array<int>
     */
    protected array $warningDays = [7, 3, 1];

    public function handle(): void
    {
        Log::info('NotifyPointsExpirationRisk: Iniciando detección de puntos próximos a vencer');

        $settings = PointsSetting::get();

        if (! $settings->expiration_months) {
            Log::info('NotifyPointsExpirationRisk: No hay configuración de expiración de puntos');

            return;
        }

        $notified = 0;

        // Para cada día de advertencia, buscar clientes que cumplan la condición
        foreach ($this->warningDays as $warningDay) {
            $notified += $this->notifyCustomersForDay($settings, $warningDay);
        }

        Log::info('NotifyPointsExpirationRisk: Completado', [
            'customers_notified' => $notified,
        ]);
    }

    /**
     * Notifica a clientes cuyos puntos vencerán en exactamente X días
     */
    protected function notifyCustomersForDay(PointsSetting $settings, int $warningDay): int
    {
        $notified = 0;

        // Calcular la fecha de inactividad que corresponde a este día de advertencia
        // Si expiration_months = 6 y warningDay = 7, buscamos clientes cuya última actividad
        // fue hace (6 meses - 7 días)
        $targetInactivityDate = now()
            ->subMonths($settings->expiration_months)
            ->addDays($warningDay);

        // Rango de 1 día para capturar clientes
        $startDate = $targetInactivityDate->copy()->startOfDay();
        $endDate = $targetInactivityDate->copy()->endOfDay();

        // Buscar clientes con puntos cuya última actividad cae en ese rango
        $customers = Customer::query()
            ->where('points', '>', 0)
            ->whereBetween('points_last_activity_at', [$startDate, $endDate])
            ->where(function ($query) {
                // No haber enviado notificación en los últimos 2 días (evita duplicados)
                $query->whereNull('points_expiration_warning_sent_at')
                    ->orWhere('points_expiration_warning_sent_at', '<', now()->subDays(2));
            })
            ->get();

        foreach ($customers as $customer) {
            try {
                $customer->notify(new PointsExpirationWarningNotification(
                    $customer->points,
                    $customer->points,
                    $warningDay
                ));

                $customer->update(['points_expiration_warning_sent_at' => now()]);

                Log::info('NotifyPointsExpirationRisk: Notificación enviada', [
                    'customer_id' => $customer->id,
                    'points' => $customer->points,
                    'days_until_expiration' => $warningDay,
                ]);

                $notified++;
            } catch (\Exception $e) {
                Log::error('NotifyPointsExpirationRisk: Error enviando notificación', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $notified;
    }
}
