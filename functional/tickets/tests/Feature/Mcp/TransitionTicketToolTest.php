<?php

namespace Functional\Tickets\Tests\Feature\Mcp;

use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Mcp\Tools\TransitionTicketTool;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Tests\Concerns\CallsTicketsMcpServer;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TransitionTicketToolTest extends TestCase
{
    use CallsTicketsMcpServer;
    use CreatesTicketProfiles;
    use RefreshDatabase;

    #[Test]
    public function it_assigns_a_ticket_through_the_action_the_rest_api_already_uses(): void
    {
        $manager = $this->createManager();
        $technician = $this->createTechnician();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $this->callingAs($manager)
            ->tool(TransitionTicketTool::class, [
                'ticket_id' => $ticket->getKey(),
                'transition' => 'assign-ticket',
                'technician_id' => $technician->getKey(),
            ])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json
                ->where('status', TicketStatus::Assigned->value)
                ->where('assigned_technician.id', $technician->getKey())
                ->etc());

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->getKey(),
            'status' => TicketStatus::Assigned->value,
            'assigned_technician_id' => $technician->getKey(),
        ]);
    }

    /**
     * The tool does not own the lifecycle table, so the refusal it hands an agent has to be
     * word for word the one the REST API answers a 409 with. Both surfaces are asked the same
     * illegal move and their two messages are compared.
     */
    #[Test]
    public function it_surfaces_the_very_conflict_the_rest_api_answers_with(): void
    {
        $manager = $this->createManager();
        $restTicket = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $mcpTicket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        Sanctum::actingAs($manager);
        $restResponse = $this->postJson('/api/v1/tickets/actions/close-ticket', [
            'resources' => [$restTicket->getKey()],
        ]);
        $restResponse->assertStatus(Response::HTTP_CONFLICT);

        $conflict = $restResponse->json('message');
        $this->assertIsString($conflict);

        $this->callingAs($manager)
            ->tool(TransitionTicketTool::class, [
                'ticket_id' => $mcpTicket->getKey(),
                'transition' => 'close-ticket',
            ])
            ->assertHasErrors([$conflict]);

        $this->assertSame(TicketStatus::Open, $mcpTicket->fresh()->status);
    }

    #[Test]
    public function it_refuses_an_assignment_from_a_user_without_the_assign_permission(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create(['status' => TicketStatus::Open]);

        $this->callingAs($requester)
            ->tool(TransitionTicketTool::class, [
                'ticket_id' => $ticket->getKey(),
                'transition' => 'assign-ticket',
                'technician_id' => $this->createTechnician()->getKey(),
            ])
            ->assertHasErrors(['This action is unauthorized.']);

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    #[Test]
    public function it_requires_the_technician_the_assign_transition_hands_the_ticket_to(): void
    {
        $manager = $this->createManager();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $this->callingAs($manager)
            ->tool(TransitionTicketTool::class, [
                'ticket_id' => $ticket->getKey(),
                'transition' => 'assign-ticket',
            ])
            ->assertHasErrors();

        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    #[Test]
    public function it_refuses_a_transition_it_does_not_expose(): void
    {
        $manager = $this->createManager();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $this->callingAs($manager)
            ->tool(TransitionTicketTool::class, [
                'ticket_id' => $ticket->getKey(),
                'transition' => 'delete-ticket',
            ])
            ->assertHasErrors();
    }

    #[Test]
    public function it_hides_a_ticket_outside_the_perimeter_from_the_transition_tool(): void
    {
        $foreignTicket = Ticket::factory()->create(['status' => TicketStatus::Resolved]);

        $this->callingAs($this->createRequester())
            ->tool(TransitionTicketTool::class, [
                'ticket_id' => $foreignTicket->getKey(),
                'transition' => 'close-ticket',
            ])
            ->assertHasErrors(["No ticket [{$foreignTicket->getKey()}] is visible to you."]);

        $this->assertSame(TicketStatus::Resolved, $foreignTicket->fresh()->status);
    }
}
