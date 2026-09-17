<?php

namespace Functional\Tickets\Access\Perimeters;

use Functional\Tickets\Enums\TicketAbility;
use Functional\Tickets\Enums\TicketPermission;
use Functional\Users\Models\User;
use Lomkit\Access\Perimeters\Perimeter;

class AllTicketsPerimeter extends Perimeter
{
    public function __construct()
    {
        parent::__construct();

        $this->allowed(fn (User $user, string $method): bool => match (TicketAbility::tryFrom($method)) {
            TicketAbility::View, TicketAbility::Update => $user->can(TicketPermission::ViewAllTickets->value),
            TicketAbility::Assign => $user->can(TicketPermission::AssignTicket->value),
            TicketAbility::Close => $user->can(TicketPermission::CloseTicket->value),
            default => false,
        });
    }
}
