<?php

namespace Functional\Tickets\Actions\Concerns;

use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Exceptions\IllegalTicketTransitionException;
use Functional\Tickets\Models\Ticket;

trait TransitionsTicketStatus
{
    /**
     * The single gate every transition goes through, so no action can move a ticket by
     * forgetting to consult the table.
     *
     * The attributes are chosen by the action, never by request input, so they are force
     * filled: `sla_met` is deliberately outside the model's mass-assignable set.
     *
     * @param array<string, mixed> $attributes
     *
     * @throws IllegalTicketTransitionException
     */
    protected function transitionTo(Ticket $ticket, TicketStatus $target, array $attributes = []): void
    {
        if (! $ticket->status->canTransitionTo($target)) {
            throw new IllegalTicketTransitionException($ticket->status, $target);
        }

        $ticket
            ->forceFill(array_merge($attributes, ['status' => $target]))
            ->save();
    }
}
