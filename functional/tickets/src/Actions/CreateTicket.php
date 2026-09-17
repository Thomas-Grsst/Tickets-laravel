<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;

class CreateTicket
{
    /**
     * @param  array{title: string, description: string, priority: string}  $attributes
     */
    public function __invoke(User $requester, array $attributes): Ticket
    {
        return Ticket::create($attributes + ['requester_id' => $requester->getKey()]);
    }
}
