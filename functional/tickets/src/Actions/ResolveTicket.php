<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Actions\Concerns\TransitionsTicketStatus;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Jobs\RecordTicketSlaCompliance;
use Functional\Tickets\Models\Ticket;

class ResolveTicket
{
    use TransitionsTicketStatus;

    /**
     * The SLA job re-reads the ticket from the database, so it is queued after the
     * surrounding transaction commits — the REST layer runs every action inside one.
     */
    public function __invoke(Ticket $ticket): void
    {
        $this->transitionTo($ticket, TicketStatus::Resolved, [
            'resolved_at' => now(),
        ]);

        RecordTicketSlaCompliance::dispatch($ticket)->afterCommit();
    }
}
