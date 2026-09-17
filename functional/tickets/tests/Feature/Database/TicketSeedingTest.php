<?php

namespace Functional\Tickets\Tests\Feature\Database;

use Database\Seeders\DatabaseSeeder;
use Functional\Tickets\Database\Seeders\TicketAccessSeeder;
use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketSeedingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_every_layer_through_the_osdd_registry(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, User::query()->count());
        $this->assertGreaterThan(0, Ticket::query()->count());
    }

    #[Test]
    public function it_gives_each_seeded_role_exactly_the_permissions_of_its_profile(): void
    {
        $this->seed(TicketAccessSeeder::class);

        $this->assertSame(
            [TicketPermission::CreateTicket->value, TicketPermission::ViewOwnTickets->value],
            $this->permissionsOf('requester'),
        );
        $this->assertSame(
            [TicketPermission::ViewAssignedTickets->value, TicketPermission::ViewOwnTickets->value],
            $this->permissionsOf('technician'),
        );
        $this->assertSame(
            [
                TicketPermission::AssignTicket->value,
                TicketPermission::CloseTicket->value,
                TicketPermission::ViewAllTickets->value,
            ],
            $this->permissionsOf('manager'),
        );
    }

    #[Test]
    public function it_seeds_one_user_per_profile_that_carries_the_matching_role(): void
    {
        $this->seed(TicketAccessSeeder::class);

        foreach (['requester', 'technician', 'manager'] as $profile) {
            $user = User::query()->where('email', "{$profile}@tickets.test")->sole();

            $this->assertTrue($user->hasRole($profile));
        }
    }

    #[Test]
    public function it_seeds_tickets_spread_across_the_three_perimeters(): void
    {
        $this->seed(TicketAccessSeeder::class);

        $requester = User::query()->where('email', 'requester@tickets.test')->sole();
        $technician = User::query()->where('email', 'technician@tickets.test')->sole();

        $this->assertSame(5, Ticket::query()->whereBelongsTo($requester, 'requester')->count());
        $this->assertSame(6, Ticket::query()->whereBelongsTo($technician, 'assignedTechnician')->count());
        $this->assertSame(1, Ticket::query()->whereBelongsTo($technician, 'requester')->count());
    }

    /**
     * @return list<string>
     */
    private function permissionsOf(string $roleName): array
    {
        $permissions = Role::findByName($roleName)
            ->permissions
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        return $permissions;
    }
}
