<?php

namespace App\Services\Driver;

use App\Models\Driver;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DriverStatsService
{
    /**
     * Períodos válidos para estadísticas.
     */
    private const VALID_PERIODS = ['today', 'week', 'month', 'year'];

    /**
     * Obtiene estadísticas completas del driver para un período.
     *
     * @param  Driver  $driver  El driver del cual obtener estadísticas
     * @param  string  $period  Período: 'today', 'week', 'month', 'year'
     * @return array{
     *     period: string,
     *     period_label: string,
     *     deliveries: array{total: int, completed: int, cancelled: int, completion_rate: float},
     *     timing: array{average_minutes: float|null, fastest_minutes: int|null, slowest_minutes: int|null},
     *     rating: array{average: float|null, total_reviews: int, distribution: array<int, int>},
     *     earnings: array{tips_total: string, tips_average: string}
     * }
     */
    public function getStats(Driver $driver, string $period = 'month'): array
    {
        // Validar período
        if (! in_array($period, self::VALID_PERIODS)) {
            $period = 'month';
        }

        [$startDate, $endDate] = $this->getDateRange($period);

        // Obtener órdenes del período
        $completedOrders = $this->getCompletedOrders($driver, $startDate, $endDate);
        $cancelledCount = $this->getCancelledCount($driver, $startDate, $endDate);

        // Calcular estadísticas
        $deliveryStats = $this->calculateDeliveryStats($completedOrders, $cancelledCount);
        $timingStats = $this->calculateTimingStats($completedOrders);
        $ratingStats = $this->calculateRatingStats($completedOrders);
        $earningsStats = $this->calculateEarningsStats($completedOrders);

        return [
            'period' => $period,
            'period_label' => $this->getPeriodLabel($period),
            'deliveries' => $deliveryStats,
            'timing' => $timingStats,
            'rating' => $ratingStats,
            'earnings' => $earningsStats,
        ];
    }

    /**
     * Obtiene el label del período para mostrar al usuario.
     *
     * @param  string  $period  El período a formatear
     */
    private function getPeriodLabel(string $period): string
    {
        return match ($period) {
            'today' => 'Hoy',
            'week' => 'Esta semana',
            'month' => now()->translatedFormat('F Y'),
            'year' => (string) now()->year,
            default => now()->translatedFormat('F Y'),
        };
    }

    /**
     * Obtiene el rango de fechas para el período.
     *
     * @param  string  $period  El período a calcular
     * @return array{0: Carbon, 1: Carbon} [start, end]
     */
    private function getDateRange(string $period): array
    {
        $now = now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * Obtiene las órdenes completadas del driver en el período.
     *
     * @param  Driver  $driver  El driver
     * @param  Carbon  $startDate  Fecha de inicio
     * @param  Carbon  $endDate  Fecha de fin
     * @return Collection<int, Order>
     */
    private function getCompletedOrders(Driver $driver, Carbon $startDate, Carbon $endDate): Collection
    {
        return Order::query()
            ->assignedToDriver($driver->id)
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->get();
    }

    /**
     * Cuenta las órdenes canceladas del driver en el período.
     *
     * @param  Driver  $driver  El driver
     * @param  Carbon  $startDate  Fecha de inicio
     * @param  Carbon  $endDate  Fecha de fin
     */
    private function getCancelledCount(Driver $driver, Carbon $startDate, Carbon $endDate): int
    {
        return Order::query()
            ->assignedToDriver($driver->id)
            ->where('status', Order::STATUS_CANCELLED)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Calcula estadísticas de entregas.
     *
     * @param  Collection<int, Order>  $completedOrders
     * @return array{total: int, completed: int, cancelled: int, completion_rate: float}
     */
    private function calculateDeliveryStats(Collection $completedOrders, int $cancelledCount): array
    {
        $completed = $completedOrders->count();
        $total = $completed + $cancelledCount;
        $completionRate = $total > 0 ? round(($completed / $total) * 100, 2) : 0.0;

        return [
            'total' => $total,
            'completed' => $completed,
            'cancelled' => $cancelledCount,
            'completion_rate' => $completionRate,
        ];
    }

    /**
     * Calcula estadísticas de tiempos de entrega.
     *
     * @param  Collection<int, Order>  $completedOrders
     * @return array{average_minutes: float|null, fastest_minutes: int|null, slowest_minutes: int|null}
     */
    private function calculateTimingStats(Collection $completedOrders): array
    {
        $ordersWithTiming = $completedOrders->filter(function (Order $order) {
            return $order->accepted_by_driver_at !== null && $order->delivered_at !== null;
        });

        if ($ordersWithTiming->isEmpty()) {
            return [
                'average_minutes' => null,
                'fastest_minutes' => null,
                'slowest_minutes' => null,
            ];
        }

        $deliveryTimes = $ordersWithTiming->map(function (Order $order) {
            return $order->accepted_by_driver_at->diffInMinutes($order->delivered_at);
        });

        return [
            'average_minutes' => round($deliveryTimes->avg(), 2),
            'fastest_minutes' => (int) $deliveryTimes->min(),
            'slowest_minutes' => (int) $deliveryTimes->max(),
        ];
    }

    /**
     * Calcula estadísticas de calificaciones.
     *
     * @param  Collection<int, Order>  $completedOrders
     * @return array{average: float|null, total_reviews: int, distribution: array<int, int>}
     */
    private function calculateRatingStats(Collection $completedOrders): array
    {
        $ordersWithRating = $completedOrders->filter(function (Order $order) {
            return $order->delivery_person_rating !== null;
        });

        $totalReviews = $ordersWithRating->count();
        $average = $totalReviews > 0 ? round($ordersWithRating->avg('delivery_person_rating'), 2) : null;

        // Calcular distribución de ratings (1-5)
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $ordersWithRating->where('delivery_person_rating', $i)->count();
        }

        return [
            'average' => $average,
            'total_reviews' => $totalReviews,
            'distribution' => $distribution,
        ];
    }

    /**
     * Calcula estadísticas de propinas.
     *
     * Nota: Actualmente el modelo Order no tiene un campo de propinas,
     * por lo que este método retorna valores en cero. Cuando se agregue
     * el campo de propinas al modelo, este método deberá ser actualizado.
     *
     * @param  Collection<int, Order>  $completedOrders
     * @return array{tips_total: string, tips_average: string}
     */
    private function calculateEarningsStats(Collection $completedOrders): array
    {
        // TODO: Actualizar cuando se agregue campo de propinas al modelo Order
        // Por ahora retornamos valores en cero formateados como moneda
        $tipsTotal = 0.0;
        $tipsAverage = 0.0;

        // Si existiera un campo 'tip' en Order:
        // $tipsTotal = $completedOrders->sum('tip') ?? 0;
        // $tipsAverage = $completedOrders->count() > 0 ? $tipsTotal / $completedOrders->count() : 0;

        return [
            'tips_total' => number_format($tipsTotal, 2),
            'tips_average' => number_format($tipsAverage, 2),
        ];
    }
}
