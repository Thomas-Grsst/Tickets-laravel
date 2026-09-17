<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private User $requester;

    private User $otherRequester;

    private User $technician;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->createProfiles();
        $this->seedTicketFixtures();
    }

    private function seedRolesAndPermissions(): void
    {
        foreach (TicketPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        Role::findOrCreate('requester')->syncPermissions([
            TicketPermission::CreateTicket->value,
            TicketPermission::ViewOwnTickets->value,
        ]);

        Role::findOrCreate('technician')->syncPermissions([
            TicketPermission::ViewAssignedTickets->value,
            TicketPermission::ViewOwnTickets->value,
        ]);

        Role::findOrCreate('manager')->syncPermissions([
            TicketPermission::ViewAllTickets->value,
            TicketPermission::AssignTicket->value,
            TicketPermission::CloseTicket->value,
        ]);
    }

    private function createProfiles(): void
    {
        $this->requester = User::factory()->create();
        $this->requester->assignRole('requester');

        $this->otherRequester = User::factory()->create();
        $this->otherRequester->assignRole('requester');

        $this->technician = User::factory()->create();
        $this->technician->assignRole('technician');

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');
    }

    private function seedTicketFixtures(): void
    {
        // Own, not assigned to the technician.
        Ticket::factory()->for($this->requester, 'requester')->create();
        Ticket::factory()->for($this->requester, 'requester')->create();

        // Someone else's ticket, assigned to the technician.
        Ticket::factory()->for($this->otherRequester, 'requester')->assignedToTechnician($this->technician)->create();

        // Someone else's ticket, not assigned to anyone the fixtures care about.
        Ticket::factory()->for($this->otherRequester, 'requester')->create();
    }

    public function test_a_requester_only_sees_their_own_tickets(): void
    {
        Sanctum::actingAs($this->requester);

        $response = $this->postJson('/api/v1/tickets/search');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));

        foreach ($response->json('data') as $ticket) {
            $this->assertContains($ticket['id'], Ticket::query()->where('requester_id', $this->requester->id)->pluck('id')->all());
        }
    }

    public function test_a_technician_only_sees_tickets_assigned_to_them(): void
    {
        Sanctum::actingAs($this->technician);

        $response = $this->postJson('/api/v1/tickets/search');

        $response->assertOk();
        $tickets = $response->json('data');

        $this->assertCount(1, $tickets);
        $this->assertSame(
            Ticket::query()->where('assigned_technician_id', $this->technician->id)->value('id'),
            $tickets[0]['id'],
        );
    }

    public function test_a_manager_sees_every_ticket(): void
    {
        Sanctum::actingAs($this->manager);

        $response = $this->postJson('/api/v1/tickets/search');

        $response->assertOk();
        $this->assertCount(Ticket::query()->count(), $response->json('data'));
    }

    public function test_the_scoping_is_applied_in_the_query_not_in_php(): void
    {
        Sanctum::actingAs($this->requester);

        $sql = Ticket::query()->controlled()->toSql();

        $this->assertStringContainsString('requester_id', $sql);
    }
}
