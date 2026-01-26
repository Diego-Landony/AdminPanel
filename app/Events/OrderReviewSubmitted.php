<?php

namespace App\Events;

use App\Models\Order;
use App\Models\OrderReview;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando un cliente envía una calificación de orden.
 *
 * Este evento permite que la app Flutter actualice la UI automáticamente
 * después de que el cliente califique una orden, evitando que pueda
 * intentar calificar de nuevo.
 */
class OrderReviewSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public OrderReview $review
    ) {}

    /**
     * Canal privado del cliente para recibir la confirmación de calificación.
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
        return 'order.review.submitted';
    }

    /**
     * Datos enviados al cliente.
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'review' => [
                'id' => $this->review->id,
                'overall_rating' => $this->review->overall_rating,
                'quality_rating' => $this->review->quality_rating,
                'speed_rating' => $this->review->speed_rating,
                'service_rating' => $this->review->service_rating,
                'comment' => $this->review->comment,
                'created_at' => $this->review->created_at->toIso8601String(),
            ],
        ];
    }
}
