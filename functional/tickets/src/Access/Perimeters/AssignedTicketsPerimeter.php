<?php

namespace Functional\Tickets\Access\Perimeters;

use Functional\Tickets\Enums\TicketAbility;
use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Lomkit\Access\Perimeters\OverlayPerimeter;

class AssignedTicketsPerimeter extends OverlayPerimeter
{
    public function __construct()
    {
        parent::__construct();

        $this->allowed(fn (User $user, string $method): bool => match (TicketAbility::tryFrom($method)) {
            TicketAbility::View, TicketAbility::Update => $user->can(TicketPermission::ViewAssignedTickets->value),
            default => false,
        });

        $this->should(fn (User $user, Ticket $ticket): bool => $user->is($ticket->assignedTechnician));

        $this->query(fn (Builder $query, User $user): Builder => $query->whereBelongsTo($user, 'assignedTechnician'));
    }
}
