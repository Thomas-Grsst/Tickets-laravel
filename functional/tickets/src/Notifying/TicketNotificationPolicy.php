<?php

namespace Functional\Tickets\Notifying;

use Functional\Tickets\Models\Ticket;

/**
 * How loudly a ticket is announced. One implementation per priority tier, so the caller
 * never learns which one it got.
 */
interface TicketNotificationPolicy
{
    public function notify(Ticket $ticket): void;
}
