<?php

namespace Functional\Tickets\Rest\Actions;

use Functional\Tickets\Actions\UnassignTicket;
use Functional\Tickets\Enums\TicketAbility;
use Functional\Tickets\Models\Ticket;

class UnassignTicketAction extends TicketTransitionAction
{
    protected function ability(): TicketAbility
    {
        return TicketAbility::Assign;
    }

    /**
     * @param array<string, mixed> $fields
     */
    protected function transition(Ticket $ticket, array $fields): void
    {
        app(UnassignTicket::class)($ticket);
    }
}
