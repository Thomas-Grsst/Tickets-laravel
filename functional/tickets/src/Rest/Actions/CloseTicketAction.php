<?php

namespace Functional\Tickets\Rest\Actions;

use Functional\Tickets\Actions\CloseTicket;
use Functional\Tickets\Enums\TicketAbility;
use Functional\Tickets\Models\Ticket;

class CloseTicketAction extends TicketTransitionAction
{
    protected function ability(): TicketAbility
    {
        return TicketAbility::Close;
    }

    /**
     * @param array<string, mixed> $fields
     */
    protected function transition(Ticket $ticket, array $fields): void
    {
        app(CloseTicket::class)($ticket);
    }
}
