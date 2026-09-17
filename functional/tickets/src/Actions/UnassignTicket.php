<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Actions\Concerns\TransitionsTicketStatus;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;

class UnassignTicket
{
    use TransitionsTicketStatus;

    public function __invoke(Ticket $ticket): void
    {
        $this->transitionTo($ticket, TicketStatus::Open, [
            'assigned_technician_id' => null,
        ]);
    }
}
