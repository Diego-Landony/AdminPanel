<?php

use App\Models\Order;
use App\Services\OrderService;

it('permite transición pickup: ready -> completed', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_READY,
        'service_type' => 'pickup',
    ]);

    $service = app(OrderService::class);
    $updated = $service->updateStatus($order, Order::STATUS_COMPLETED, null, 'system', null);

    expect($updated->status)->toBe(Order::STATUS_COMPLETED);
});

it('rechaza transición pickup: ready -> out_for_delivery', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_READY,
        'service_type' => 'pickup',
    ]);

    $service = app(OrderService::class);

    expect(fn () => $service->updateStatus($order, Order::STATUS_OUT_FOR_DELIVERY, null, 'system', null))
        ->toThrow(InvalidArgumentException::class);
});

it('permite transición delivery: ready -> out_for_delivery', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_READY,
        'service_type' => 'delivery',
    ]);

    $service = app(OrderService::class);
    $updated = $service->updateStatus($order, Order::STATUS_OUT_FOR_DELIVERY, null, 'system', null);

    expect($updated->status)->toBe(Order::STATUS_OUT_FOR_DELIVERY);
});

it('rechaza transición delivery: ready -> completed directamente', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_READY,
        'service_type' => 'delivery',
    ]);

    $service = app(OrderService::class);

    expect(fn () => $service->updateStatus($order, Order::STATUS_COMPLETED, null, 'system', null))
        ->toThrow(InvalidArgumentException::class);
});

it('permite flujo completo delivery: ready -> out_for_delivery -> delivered -> completed', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_READY,
        'service_type' => 'delivery',
    ]);

    $service = app(OrderService::class);

    $order = $service->updateStatus($order, Order::STATUS_OUT_FOR_DELIVERY, null, 'system', null);
    expect($order->status)->toBe(Order::STATUS_OUT_FOR_DELIVERY);

    $order = $service->updateStatus($order, Order::STATUS_DELIVERED, null, 'system', null);
    expect($order->status)->toBe(Order::STATUS_DELIVERED);

    $order = $service->updateStatus($order, Order::STATUS_COMPLETED, null, 'system', null);
    expect($order->status)->toBe(Order::STATUS_COMPLETED);
});
