<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Actions\Concerns\TransitionsTicketStatus;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;

class CloseTicket
{
    use TransitionsTicketStatus;

    public function __invoke(Ticket $ticket): void
    {
        $this->transitionTo($ticket, TicketStatus::Closed);
    }
}
