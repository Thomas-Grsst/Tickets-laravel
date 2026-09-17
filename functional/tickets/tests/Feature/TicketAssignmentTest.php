<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Notifications\TicketAssignedNotification;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $technician;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (TicketPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        Role::findOrCreate('manager')->syncPermissions([
            TicketPermission::ViewAllTickets->value,
            TicketPermission::AssignTicket->value,
            TicketPermission::CloseTicket->value,
        ]);

        Role::findOrCreate('technician')->syncPermissions([
            TicketPermission::ViewAssignedTickets->value,
        ]);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');

        $this->technician = User::factory()->create();
        $this->technician->assignRole('technician');
    }

    public function test_assigning_a_ticket_notifies_the_technician(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->manager);

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $response = $this->postJson('/api/v1/tickets/actions/assign-ticket', [
            'resources' => [$ticket->id],
            'fields' => [['name' => 'technician_id', 'value' => $this->technician->id]],
        ]);

        $response->assertOk();

        $ticket->refresh();
        $this->assertSame(TicketStatus::Assigned, $ticket->status);
        $this->assertSame($this->technician->id, $ticket->assigned_technician_id);

        Notification::assertSentTo($this->technician, TicketAssignedNotification::class);
    }

    public function test_an_illegal_transition_is_refused_with_a_conflict(): void
    {
        Sanctum::actingAs($this->manager);

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $response = $this->postJson('/api/v1/tickets/actions/close-ticket', [
            'resources' => [$ticket->id],
        ]);

        $response->assertStatus(409);

        $ticket->refresh();
        $this->assertSame(TicketStatus::Open, $ticket->status);
    }

    public function test_a_legal_transition_chain_reaches_closed(): void
    {
        Sanctum::actingAs($this->manager);

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $this->postJson('/api/v1/tickets/actions/assign-ticket', [
            'resources' => [$ticket->id],
            'fields' => [['name' => 'technician_id', 'value' => $this->technician->id]],
        ])->assertOk();

        $this->postJson('/api/v1/tickets/actions/start-ticket-progress', [
            'resources' => [$ticket->id],
        ])->assertOk();

        $this->postJson('/api/v1/tickets/actions/resolve-ticket', [
            'resources' => [$ticket->id],
        ])->assertOk();

        $this->postJson('/api/v1/tickets/actions/close-ticket', [
            'resources' => [$ticket->id],
        ])->assertOk();

        $ticket->refresh();
        $this->assertSame(TicketStatus::Closed, $ticket->status);
        $this->assertNotNull($ticket->resolved_at);
    }

    public function test_unassigning_returns_a_ticket_to_open(): void
    {
        Sanctum::actingAs($this->manager);

        $ticket = Ticket::factory()->assignedToTechnician($this->technician)->create();

        $this->postJson('/api/v1/tickets/actions/unassign-ticket', [
            'resources' => [$ticket->id],
        ])->assertOk();

        $ticket->refresh();
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertNull($ticket->assigned_technician_id);
    }

    public function test_reopening_a_resolved_ticket_clears_its_sla_verdict(): void
    {
        Sanctum::actingAs($this->manager);

        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Resolved,
            'resolved_at' => now(),
            'sla_met' => true,
        ]);

        $this->postJson('/api/v1/tickets/actions/reopen-ticket', [
            'resources' => [$ticket->id],
        ])->assertOk();

        $ticket->refresh();
        $this->assertSame(TicketStatus::InProgress, $ticket->status);
        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->sla_met);
    }
}
