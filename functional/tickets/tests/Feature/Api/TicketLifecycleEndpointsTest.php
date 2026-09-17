<?php

namespace Functional\Tickets\Tests\Feature\Api;

use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketLifecycleEndpointsTest extends TestCase
{
    use CreatesTicketProfiles;
    use RefreshDatabase;

    private User $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->technician = $this->createTechnician();
    }

    #[Test]
    public function it_starts_progress_through_the_start_ticket_progress_action(): void
    {
        $ticket = Ticket::factory()->assignedToTechnician($this->technician)->create();
        Sanctum::actingAs($this->technician);

        $this->postJson('/api/v1/tickets/actions/start-ticket-progress', [
            'resources' => [$ticket->getKey()],
        ])->assertSuccessful();

        $this->assertSame(TicketStatus::InProgress, $ticket->fresh()->status);
    }

    #[Test]
    public function it_resolves_a_ticket_in_progress_through_the_resolve_action(): void
    {
        $ticket = Ticket::factory()
            ->assignedToTechnician($this->technician)
            ->create(['status' => TicketStatus::InProgress]);
        Sanctum::actingAs($this->technician);

        $this->postJson('/api/v1/tickets/actions/resolve-ticket', [
            'resources' => [$ticket->getKey()],
        ])->assertSuccessful();

        $resolved = $ticket->fresh();
        $this->assertSame(TicketStatus::Resolved, $resolved->status);
        $this->assertNotNull($resolved->resolved_at);
    }

    #[Test]
    public function it_reopens_a_resolved_ticket_through_the_reopen_action(): void
    {
        $ticket = Ticket::factory()
            ->assignedToTechnician($this->technician)
            ->create(['status' => TicketStatus::Resolved, 'resolved_at' => now()->subHour()]);
        Sanctum::actingAs($this->technician);

        $this->postJson('/api/v1/tickets/actions/reopen-ticket', [
            'resources' => [$ticket->getKey()],
        ])->assertSuccessful();

        $reopened = $ticket->fresh();
        $this->assertSame(TicketStatus::InProgress, $reopened->status);
        $this->assertNull($reopened->resolved_at);
    }

    #[Test]
    public function it_unassigns_a_ticket_through_the_unassign_action(): void
    {
        $manager = $this->createManager();
        $ticket = Ticket::factory()->assignedToTechnician($this->technician)->create();
        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/tickets/actions/unassign-ticket', [
            'resources' => [$ticket->getKey()],
        ])->assertSuccessful();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->getKey(),
            'status' => TicketStatus::Open->value,
            'assigned_technician_id' => null,
        ]);
    }

    #[Test]
    public function it_skips_a_ticket_the_caller_is_not_allowed_to_see(): void
    {
        $foreignTicket = Ticket::factory()->create(['status' => TicketStatus::Assigned]);
        Sanctum::actingAs($this->technician);

        $this->postJson('/api/v1/tickets/actions/start-ticket-progress', [
            'resources' => [$foreignTicket->getKey()],
        ]);

        $this->assertSame(TicketStatus::Assigned, $foreignTicket->fresh()->status);
    }

    #[Test]
    public function it_exposes_the_resource_schema_on_the_details_endpoint(): void
    {
        Sanctum::actingAs($this->createManager());

        $this->getJson('/api/v1/tickets')
            ->assertSuccessful()
            ->assertJsonPath('data.fields', ['id', 'title', 'description', 'status', 'priority', 'created_at', 'resolved_at', 'sla_met']);
    }

    #[Test]
    public function it_refuses_to_delete_a_ticket_through_the_api(): void
    {
        $manager = $this->createManager();
        $ticket = Ticket::factory()->create();
        Sanctum::actingAs($manager);

        $this->deleteJson('/api/v1/tickets', ['resources' => [$ticket->getKey()]])
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->getKey(), 'deleted_at' => null]);
    }
}
