<?php

namespace Functional\Tickets\Broadcasting;

use Functional\Tickets\Models\Ticket;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * The single place a ticket's channel name is written, so the events that broadcast on it
 * and the route that authorises it can never drift apart.
 *
 * The channel is per ticket and always private: membership is decided by the same access
 * control that scopes the list query, and a browser never subscribes to a ticket the
 * server has not first agreed to show it.
 */
class TicketChannel
{
    private const PREFIX = 'tickets.';

    /** The pattern `Broadcast::channel()` authorises, with the ticket as a bound model. */
    public const ROUTE = self::PREFIX . '{ticket}';

    public static function for(Ticket $ticket): PrivateChannel
    {
        return new PrivateChannel(self::PREFIX . $ticket->getKey());
    }
}
