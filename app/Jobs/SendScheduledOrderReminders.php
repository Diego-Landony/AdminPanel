<?php

namespace App\Jobs;

use App\Models\Order;
use App\Notifications\ScheduledOrderReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Envía recordatorios de pedidos programados.
 *
 * Busca pedidos con pickup o delivery programado en los próximos 30 minutos
 * que aún no han sido notificados.
 */
class SendScheduledOrderReminders implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $reminderWindowStart = now()->addMinutes(25);
        $reminderWindowEnd = now()->addMinutes(35);

        $orders = Order::query()
            ->where(function ($query) use ($reminderWindowStart, $reminderWindowEnd) {
                $query->where(function ($q) use ($reminderWindowStart, $reminderWindowEnd) {
                    $q->whereNotNull('scheduled_pickup_time')
                        ->whereBetween('scheduled_pickup_time', [$reminderWindowStart, $reminderWindowEnd]);
                })->orWhere(function ($q) use ($reminderWindowStart, $reminderWindowEnd) {
                    $q->whereNotNull('scheduled_for')
                        ->whereBetween('scheduled_for', [$reminderWindowStart, $reminderWindowEnd]);
                });
            })
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PREPARING])
            ->whereNull('scheduled_reminder_sent_at')
            ->with(['customer', 'restaurant'])
            ->get();

        foreach ($orders as $order) {
            if ($order->customer) {
                $order->customer->notify(new ScheduledOrderReminderNotification($order));

                $order->update(['scheduled_reminder_sent_at' => now()]);

                Log::info('ScheduledOrderReminder: Enviado', [
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'service_type' => $order->service_type,
                    'scheduled_time' => $order->scheduled_pickup_time ?? $order->scheduled_for,
                ]);
            }
        }
    }
}
