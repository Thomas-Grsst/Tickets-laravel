<?php

namespace Functional\Tickets\Policies;

use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return true;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return true;
    }
}
