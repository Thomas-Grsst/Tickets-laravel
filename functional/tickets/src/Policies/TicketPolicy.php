<?php

namespace Functional\Tickets\Policies;

use Functional\Tickets\Access\Controls\TicketControl;
use Functional\Tickets\Enums\TicketAbility;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Lomkit\Access\Policies\ControlledPolicy;

class TicketPolicy extends ControlledPolicy
{
    /** @var class-string<TicketControl> */
    protected string $control = TicketControl::class;

    /**
     * Assignment and closing are their own abilities rather than a flavour of `update`,
     * so the perimeters can require `tickets.assign` / `tickets.close` for them.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        return $this->getControl()->applies($user, TicketAbility::Assign->value, $ticket);
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $this->getControl()->applies($user, TicketAbility::Close->value, $ticket);
    }
}
