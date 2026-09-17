<?php

namespace Functional\Tickets\Rest\Actions;

use Functional\Tickets\Actions\ReopenTicket;
use Functional\Tickets\Enums\TicketAbility;
use Functional\Tickets\Models\Ticket;

class ReopenTicketAction extends TicketTransitionAction
{
    protected function ability(): TicketAbility
    {
        return TicketAbility::Update;
    }

    /**
     * @param array<string, mixed> $fields
     */
    protected function transition(Ticket $ticket, array $fields): void
    {
        app(ReopenTicket::class)($ticket);
    }
}
