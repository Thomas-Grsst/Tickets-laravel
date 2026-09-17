<?php

namespace Functional\Tickets\Notifying\Channels;

use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Notifications\TicketNeedsHandlingNotification;
use Functional\Tickets\Notifying\TicketManagers;
use Illuminate\Support\Facades\Notification;

/**
 * The floor every tier stands on: a mail notification to the people who own the ticket's
 * handling.
 */
class TicketMailChannel implements TicketNotificationChannel
{
    public function __construct(private readonly TicketManagers $managers)
    {
    }

    public function deliver(Ticket $ticket): void
    {
        Notification::send(($this->managers)(), new TicketNeedsHandlingNotification($ticket));
    }
}
