<?php

namespace Functional\Tickets\Notifying;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Users\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

/**
 * The people a ticket is escalated to are the people allowed to re-assign it, resolved by
 * the right they hold and never by a role name.
 */
class TicketManagers
{
    /**
     * Spatie refuses a permission name it has never seen, so an unseeded right is answered
     * with an empty audience instead: nobody holds it, which is exactly what the caller
     * asked about.
     *
     * @return Collection<int, User>
     */
    public function __invoke(): Collection
    {
        $assignment = TicketPermission::AssignTicket->value;

        if (! Permission::query()->where('name', $assignment)->exists()) {
            return new Collection();
        }

        return User::query()
            ->permission($assignment)
            ->get();
    }
}
