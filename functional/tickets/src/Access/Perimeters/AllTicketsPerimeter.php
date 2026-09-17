<?php

namespace Functional\Tickets\Access\Perimeters;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Users\Models\User;
use Lomkit\Access\Perimeters\Perimeter;

class AllTicketsPerimeter extends Perimeter
{
    private const VIEW = 'view';

    public function __construct()
    {
        parent::__construct();

        $this->allowed(fn (User $user, string $method): bool => match ($method) {
            self::VIEW => $user->can(TicketPermission::ViewAllTickets->value),
            default => false,
        });
    }
}
