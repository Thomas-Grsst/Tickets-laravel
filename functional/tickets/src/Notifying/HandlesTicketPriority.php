<?php

namespace Functional\Tickets\Notifying;

use Functional\Tickets\Enums\TicketPriority;

/**
 * Each policy names the tier it owns, so the resolver builds its map by asking rather than
 * by holding a list every new tier would have to be added to.
 */
interface HandlesTicketPriority
{
    public function handles(): TicketPriority;
}
