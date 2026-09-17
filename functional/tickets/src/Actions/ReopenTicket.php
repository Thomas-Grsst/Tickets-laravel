<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Actions\Concerns\TransitionsTicketStatus;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;

class ReopenTicket
{
    use TransitionsTicketStatus;

    /**
     * A reopened ticket loses its resolution and its SLA verdict, so the next resolve
     * is measured again from scratch.
     */
    public function __invoke(Ticket $ticket): void
    {
        $this->transitionTo($ticket, TicketStatus::InProgress, [
            'resolved_at' => null,
            'sla_met' => null,
        ]);
    }
}
