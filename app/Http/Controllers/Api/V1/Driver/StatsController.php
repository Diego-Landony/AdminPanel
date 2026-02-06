<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Driver\DriverStatsResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StatsController extends Controller
{
    /**
     * Get consolidated statistics for the authenticated driver.
     *
     * GET /api/v1/driver/stats
     *
     * Query params:
     * - period: string (optional) - today, week, month, year (default: month)
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'period' => ['nullable', 'string', 'in:today,week,month,year'],
        ]);

        $driver = auth('driver')->user();
        $period = $request->input('period', 'month');

        $stats = $this->calculateStats($driver->id, $period);

        return response()->json([
            'success' => true,
            'data' => DriverStatsResource::make($stats),
            'message' => 'Estadísticas obtenidas correctamente.',
        ]);
    }

    /**
     * Calculate comprehensive statistics for the driver.
     *
     * @return array<string, mixed>
     */
    private function calculateStats(int $driverId, string $period): array
    {
        $dateRange = $this->getDateRange($period);

        // Base query for completed deliveries in period
        $baseQuery = Order::query()
            ->where('driver_id', $driverId)
            ->whereBetween('delivered_at', [$dateRange['start'], $dateRange['end']]);

        // Delivery counts
        $completedOrders = (clone $baseQuery)
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->get();

        $cancelledCount = (clone $baseQuery)
            ->where('status', Order::STATUS_CANCELLED)
            ->count();

        $totalCount = $completedOrders->count() + $cancelledCount;
        $completedCount = $completedOrders->count();
        $completionRate = $totalCount > 0
            ? round(($completedCount / $totalCount) * 100, 2)
            : 0;

        // Timing stats
        $ordersWithTiming = $completedOrders->filter(function ($order) {
            return $order->accepted_by_driver_at !== null && $order->delivered_at !== null;
        });

        $timingStats = $this->calculateTimingStats($ordersWithTiming);

        // Rating stats
        $ordersWithRating = $completedOrders->filter(function ($order) {
            return $order->delivery_person_rating !== null;
        });

        $ratingStats = $this->calculateRatingStats($ordersWithRating);

        // Tips (if applicable - using a hypothetical tip field)
        $tipsTotal = $completedOrders->sum('tip') ?? 0;
        $tipsAverage = $completedCount > 0 ? $tipsTotal / $completedCount : 0;

        return [
            'period' => $period,
            'period_label' => $this->getPeriodLabel($period, $dateRange),
            'deliveries' => [
                'total' => $totalCount,
                'completed' => $completedCount,
                'cancelled' => $cancelledCount,
                'completion_rate' => $completionRate,
            ],
            'timing' => $timingStats,
            'rating' => $ratingStats,
            'earnings' => [
                'tips_total' => number_format($tipsTotal, 2, '.', ''),
                'tips_average' => number_format($tipsAverage, 2, '.', ''),
            ],
        ];
    }

    /**
     * Get date range for the given period.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    private function getDateRange(string $period): array
    {
        return match ($period) {
            'today' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'week' => [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
            ],
            'month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
            'year' => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear(),
            ],
            default => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
        };
    }

    /**
     * Get human-readable period label.
     *
     * @param  array{start: Carbon, end: Carbon}  $dateRange
     */
    private function getPeriodLabel(string $period, array $dateRange): string
    {
        return match ($period) {
            'today' => 'Hoy, '.$dateRange['start']->format('d M Y'),
            'week' => 'Semana del '.$dateRange['start']->format('d').' al '.$dateRange['end']->format('d M Y'),
            'month' => $dateRange['start']->translatedFormat('F Y'),
            'year' => $dateRange['start']->format('Y'),
            default => $dateRange['start']->translatedFormat('F Y'),
        };
    }

    /**
     * Calculate timing statistics from orders.
     *
     * @param  \Illuminate\Support\Collection<int, Order>  $orders
     * @return array{average_minutes: int|null, fastest_minutes: int|null, slowest_minutes: int|null}
     */
    private function calculateTimingStats($orders): array
    {
        if ($orders->isEmpty()) {
            return [
                'average_minutes' => null,
                'fastest_minutes' => null,
                'slowest_minutes' => null,
            ];
        }

        $deliveryTimes = $orders->map(function ($order) {
            return $order->accepted_by_driver_at->diffInMinutes($order->delivered_at);
        });

        return [
            'average_minutes' => (int) round($deliveryTimes->avg()),
            'fastest_minutes' => (int) $deliveryTimes->min(),
            'slowest_minutes' => (int) $deliveryTimes->max(),
        ];
    }

    /**
     * Calculate rating statistics from orders.
     *
     * @param  \Illuminate\Support\Collection<int, Order>  $orders
     * @return array{average: float|null, total_reviews: int, distribution: array<string, int>}
     */
    private function calculateRatingStats($orders): array
    {
        $distribution = [
            '5' => 0,
            '4' => 0,
            '3' => 0,
            '2' => 0,
            '1' => 0,
        ];

        if ($orders->isEmpty()) {
            return [
                'average' => null,
                'total_reviews' => 0,
                'distribution' => $distribution,
            ];
        }

        // Calculate distribution
        foreach ($orders as $order) {
            $rating = (string) $order->delivery_person_rating;
            if (isset($distribution[$rating])) {
                $distribution[$rating]++;
            }
        }

        return [
            'average' => round($orders->avg('delivery_person_rating'), 1),
            'total_reviews' => $orders->count(),
            'distribution' => $distribution,
        ];
    }
}
