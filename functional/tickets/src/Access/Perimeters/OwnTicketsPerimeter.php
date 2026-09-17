<?php

namespace Functional\Tickets\Access\Perimeters;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Lomkit\Access\Perimeters\OverlayPerimeter;

class OwnTicketsPerimeter extends OverlayPerimeter
{
    private const VIEW = 'view';

    private const CREATE = 'create';

    public function __construct()
    {
        parent::__construct();

        $this->allowed(fn (User $user, string $method): bool => match ($method) {
            self::VIEW => $user->can(TicketPermission::ViewOwnTickets->value),
            self::CREATE => $user->can(TicketPermission::CreateTicket->value),
            default => false,
        });

        $this->should(fn (User $user, Ticket $ticket): bool => $user->is($ticket->requester));

        $this->query(fn (Builder $query, User $user): Builder => $query->whereBelongsTo($user, 'requester'));
    }
}
