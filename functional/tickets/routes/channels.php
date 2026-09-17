<?php

use Functional\Tickets\Broadcasting\TicketChannel;
use Functional\Tickets\Enums\TicketAbility;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Support\Facades\Broadcast;

/**
 * Subscribing to a ticket's channel is the same question as seeing it in the list, so it
 * is answered by the same policy — and therefore by the same perimeters that scope
 * `Ticket::query()->controlled()`. A requester outside a ticket's perimeter is refused at
 * `/broadcasting/auth` and never receives a single message about it.
 */
Broadcast::channel(
    TicketChannel::ROUTE,
    static fn (User $user, Ticket $ticket): bool => $user->can(TicketAbility::View->value, $ticket),
);
