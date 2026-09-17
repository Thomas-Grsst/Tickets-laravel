<?php

namespace Functional\Tickets\Escalations;

use Functional\Tickets\Models\Ticket;
use Illuminate\Support\Collection;

/**
 * What one escalation run did, so the command can report it without re-querying: the
 * tickets whose priority was raised, and the ones already at the top of the ladder that
 * are overdue but can only be flagged.
 */
readonly class OverdueTicketsEscalation
{
    /**
     * @param Collection<int, Ticket> $escalatedTickets
     * @param Collection<int, Ticket> $cappedTickets
     */
    public function __construct(
        public Collection $escalatedTickets,
        public Collection $cappedTickets,
    ) {
    }

    public function overdueCount(): int
    {
        return $this->escalatedTickets->count() + $this->cappedTickets->count();
    }
}
