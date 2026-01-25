<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando una orden se completa para solicitar calificación al cliente.
 *
 * Este evento se emite únicamente cuando la orden llega al estado 'completed',
 * permitiendo que la app Flutter muestre el modal de calificación.
 */
class RequestOrderReview implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    /**
     * Canal privado del cliente para recibir la solicitud de calificación.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('customer.'.$this->order->customer_id.'.orders'),
        ];
    }

    /**
     * Nombre del evento para el cliente.
     */
    public function broadcastAs(): string
    {
        return 'order.review.requested';
    }

    /**
     * Datos enviados al cliente.
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'restaurant' => [
                'id' => $this->order->restaurant_id,
                'name' => $this->order->restaurant?->name,
            ],
            'total' => $this->order->total,
            'service_type' => $this->order->service_type,
            'completed_at' => now()->toIso8601String(),
        ];
    }
}
