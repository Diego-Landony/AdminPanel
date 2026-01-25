<?php

use App\Models\SupportTicket;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('support.ticket.{ticketId}', function ($user, $ticketId) {
    $ticket = SupportTicket::find($ticketId);

    if (! $ticket) {
        return false;
    }

    if ($user instanceof \App\Models\User) {
        return true;
    }

    if ($user instanceof \App\Models\Customer) {
        return $ticket->customer_id === $user->id;
    }

    return false;
});

Broadcast::channel('support.admin', function ($user) {
    return $user instanceof \App\Models\User;
});

// Canal privado para notificaciones del cliente (soporte, etc.)
Broadcast::channel('customer.{customerId}', function ($user, $customerId) {
    if ($user instanceof \App\Models\Customer) {
        return $user->id === (int) $customerId;
    }

    return false;
});

// Canal privado para órdenes del cliente
Broadcast::channel('customer.{customerId}.orders', function ($user, $customerId) {
    if ($user instanceof \App\Models\Customer) {
        return $user->id === (int) $customerId;
    }

    return false;
});

// Canal privado para órdenes del restaurante
Broadcast::channel('restaurant.{restaurantId}.orders', function ($user, $restaurantId) {
    if ($user instanceof \App\Models\RestaurantUser) {
        return $user->restaurant_id === (int) $restaurantId;
    }

    return false;
});
