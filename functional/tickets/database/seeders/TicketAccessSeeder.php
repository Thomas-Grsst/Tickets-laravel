<?php

namespace Functional\Tickets\Database\Seeders;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TicketAccessSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (TicketPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        $requester = $this->createProfile('requester', [
            TicketPermission::CreateTicket,
            TicketPermission::ViewOwnTickets,
        ]);

        $technician = $this->createProfile('technician', [
            TicketPermission::ViewAssignedTickets,
            TicketPermission::ViewOwnTickets,
        ]);

        $this->createProfile('manager', [
            TicketPermission::ViewAllTickets,
            TicketPermission::AssignTicket,
            TicketPermission::CloseTicket,
        ]);

        $this->createPerimeterFixture($requester, $technician);
    }

    /**
     * @param list<TicketPermission> $permissions
     */
    private function createProfile(string $roleName, array $permissions): User
    {
        Role::findOrCreate($roleName)
            ->syncPermissions(array_map(
                static fn (TicketPermission $permission): string => $permission->value,
                $permissions,
            ));

        $user = User::factory()->create([
            'name' => Str::headline($roleName),
            'email' => "{$roleName}@tickets.test",
        ]);

        $user->assignRole($roleName);

        return $user;
    }

    private function createPerimeterFixture(User $requester, User $technician): void
    {
        Ticket::factory()
            ->count(3)
            ->for($requester, 'requester')
            ->create();

        Ticket::factory()
            ->count(2)
            ->for($requester, 'requester')
            ->assignedToTechnician($technician)
            ->create();

        Ticket::factory()
            ->count(4)
            ->assignedToTechnician($technician)
            ->create();

        Ticket::factory()
            ->for($technician, 'requester')
            ->assignedToTechnician()
            ->create();
    }
}
