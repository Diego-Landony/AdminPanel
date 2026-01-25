<?php

namespace App\Events;

use App\Http\Resources\Api\V1\Order\OrderResource;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public string $previousStatus
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('customer.'.$this->order->customer_id.'.orders'),
            new PrivateChannel('restaurant.'.$this->order->restaurant_id.'.orders'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'order' => new OrderResource($this->order),
            'previous_status' => $this->previousStatus,
            'new_status' => $this->order->status,
        ];
    }
}
