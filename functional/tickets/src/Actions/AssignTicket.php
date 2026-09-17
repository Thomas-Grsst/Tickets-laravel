<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Actions\Concerns\TransitionsTicketStatus;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Events\TicketAssigned;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;

class AssignTicket
{
    use TransitionsTicketStatus;

    public function __invoke(Ticket $ticket, User $technician): void
    {
        $this->transitionTo($ticket, TicketStatus::Assigned, [
            'assigned_technician_id' => $technician->getKey(),
        ]);

        TicketAssigned::dispatch($ticket, $technician);
    }
}
