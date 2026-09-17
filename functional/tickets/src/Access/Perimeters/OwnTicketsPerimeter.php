<?php

namespace Functional\Tickets\Access\Perimeters;

use Functional\Tickets\Enums\TicketAbility;
use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Lomkit\Access\Perimeters\OverlayPerimeter;

class OwnTicketsPerimeter extends OverlayPerimeter
{
    public function __construct()
    {
        parent::__construct();

        $this->allowed(fn (User $user, string $method): bool => match (TicketAbility::tryFrom($method)) {
            TicketAbility::View => $user->can(TicketPermission::ViewOwnTickets->value),
            TicketAbility::Create => $user->can(TicketPermission::CreateTicket->value),
            default => false,
        });

        $this->should(fn (User $user, Ticket $ticket): bool => $user->is($ticket->requester));

        $this->query(fn (Builder $query, User $user): Builder => $query->whereBelongsTo($user, 'requester'));
    }
}
