<?php

namespace Functional\Tickets\Tests\Concerns;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Users\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * The three profiles the access seeder ships, rebuilt from permissions so a test never
 * depends on seeder row counts.
 */
trait CreatesTicketProfiles
{
    protected function createRequester(): User
    {
        return $this->createUserWithPermissions([
            TicketPermission::CreateTicket,
            TicketPermission::ViewOwnTickets,
        ]);
    }

    protected function createTechnician(): User
    {
        return $this->createUserWithPermissions([
            TicketPermission::ViewAssignedTickets,
            TicketPermission::ViewOwnTickets,
        ]);
    }

    protected function createManager(): User
    {
        return $this->createUserWithPermissions([
            TicketPermission::ViewAllTickets,
            TicketPermission::AssignTicket,
            TicketPermission::CloseTicket,
        ]);
    }

    /**
     * @param list<TicketPermission> $permissions
     */
    protected function createUserWithPermissions(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission->value));
        }

        return $user;
    }
}
