<?php

namespace Functional\Tickets\Notifying\Channels;

use Functional\Tickets\Models\Ticket;

/**
 * One way of getting a ticket in front of someone. Tiers are built by composing these, so a
 * tier never carries delivery code of its own.
 */
interface TicketNotificationChannel
{
    public function deliver(Ticket $ticket): void;
}
