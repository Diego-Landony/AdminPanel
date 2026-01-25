<?php

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

it('dispara evento OrderStatusUpdated al cambiar estado', function () {
    Event::fake([OrderStatusUpdated::class]);
    Notification::fake();

    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING,
        'service_type' => 'pickup',
    ]);

    $service = app(OrderService::class);
    $service->updateStatus($order, Order::STATUS_PREPARING, null, 'system', null);

    Event::assertDispatched(OrderStatusUpdated::class, function ($event) use ($order) {
        return $event->order->id === $order->id
            && $event->previousStatus === Order::STATUS_PENDING;
    });
});

it('evento broadcast tiene canales correctos', function () {
    $order = Order::factory()->create();
    $event = new OrderStatusUpdated($order, 'pending');

    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(2);
    expect($channels[0]->name)->toBe('private-customer.'.$order->customer_id.'.orders');
    expect($channels[1]->name)->toBe('private-restaurant.'.$order->restaurant_id.'.orders');
});

it('evento broadcast tiene nombre correcto', function () {
    $order = Order::factory()->create();
    $event = new OrderStatusUpdated($order, 'pending');

    expect($event->broadcastAs())->toBe('order.status.updated');
});

it('evento broadcast incluye datos correctos', function () {
    $order = Order::factory()->create(['status' => Order::STATUS_PREPARING]);
    $event = new OrderStatusUpdated($order, Order::STATUS_PENDING);

    $data = $event->broadcastWith();

    expect($data)->toHaveKeys(['order', 'previous_status', 'new_status']);
    expect($data['previous_status'])->toBe(Order::STATUS_PENDING);
    expect($data['new_status'])->toBe(Order::STATUS_PREPARING);
});
