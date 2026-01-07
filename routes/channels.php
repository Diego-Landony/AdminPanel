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
