<?php

namespace Functional\Tickets\Exceptions;

use Functional\Tickets\Models\Ticket;
use RuntimeException;

/**
 * Never a client's fault: the SLA job only ever runs off a resolved ticket, so reaching it
 * without a resolution means the job read state the transition had not committed.
 */
class TicketSlaNotMeasurableException extends RuntimeException
{
    public function __construct(Ticket $ticket)
    {
        parent::__construct(sprintf(
            'Ticket #%s is not measurable against its SLA: status %s, resolved_at %s.',
            $ticket->getKey(),
            $ticket->status->value,
            $ticket->resolved_at?->toIso8601String() ?? 'null',
        ));
    }
}
