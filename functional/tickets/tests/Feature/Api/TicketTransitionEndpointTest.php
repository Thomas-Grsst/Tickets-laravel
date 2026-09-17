<?php

namespace Functional\Tickets\Tests\Feature\Api;

use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TicketTransitionEndpointTest extends TestCase
{
    use CreatesTicketProfiles;
    use RefreshDatabase;

    #[Test]
    public function it_assigns_a_ticket_through_the_assign_action(): void
    {
        $manager = $this->createManager();
        $technician = $this->createTechnician();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);
        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/tickets/actions/assign-ticket', [
            'resources' => [$ticket->getKey()],
            'fields' => [['name' => 'technician_id', 'value' => $technician->getKey()]],
        ])->assertSuccessful();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->getKey(),
            'status' => TicketStatus::Assigned->value,
            'assigned_technician_id' => $technician->getKey(),
        ]);
    }

    #[Test]
    public function it_answers_with_a_conflict_when_the_transition_is_illegal(): void
    {
        $manager = $this->createManager();
        $technician = $this->createTechnician();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Closed]);
        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/tickets/actions/assign-ticket', [
            'resources' => [$ticket->getKey()],
            'fields' => [['name' => 'technician_id', 'value' => $technician->getKey()]],
        ])->assertStatus(Response::HTTP_CONFLICT);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->getKey(),
            'status' => TicketStatus::Closed->value,
            'assigned_technician_id' => null,
        ]);
    }

    #[Test]
    public function it_answers_with_a_conflict_when_closing_a_ticket_that_is_not_resolved(): void
    {
        $manager = $this->createManager();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);
        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/tickets/actions/close-ticket', [
            'resources' => [$ticket->getKey()],
        ])->assertStatus(Response::HTTP_CONFLICT);

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    #[Test]
    public function it_refuses_an_assignment_from_a_user_without_the_assign_permission(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create(['status' => TicketStatus::Open]);
        Sanctum::actingAs($requester);

        $this->postJson('/api/v1/tickets/actions/assign-ticket', [
            'resources' => [$ticket->getKey()],
            'fields' => [['name' => 'technician_id', 'value' => User::factory()->create()->getKey()]],
        ])->assertForbidden();

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    #[Test]
    public function it_requires_the_caller_to_name_the_tickets_it_transitions(): void
    {
        $manager = $this->createManager();
        Ticket::factory()->count(3)->create(['status' => TicketStatus::Resolved]);
        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/tickets/actions/close-ticket', [])->assertStatus(422);

        $this->assertDatabaseMissing('tickets', ['status' => TicketStatus::Closed->value]);
    }
}
