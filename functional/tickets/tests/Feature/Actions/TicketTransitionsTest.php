<?php

namespace Functional\Tickets\Tests\Feature\Actions;

use Functional\Tickets\Actions\AssignTicket;
use Functional\Tickets\Actions\CloseTicket;
use Functional\Tickets\Actions\ReopenTicket;
use Functional\Tickets\Actions\ResolveTicket;
use Functional\Tickets\Actions\StartTicketProgress;
use Functional\Tickets\Actions\UnassignTicket;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Exceptions\IllegalTicketTransitionException;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketTransitionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_assigns_an_open_ticket_to_a_technician(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $technician = User::factory()->create();

        app(AssignTicket::class)($ticket, $technician);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->getKey(),
            'status' => TicketStatus::Assigned->value,
            'assigned_technician_id' => $technician->getKey(),
        ]);
    }

    #[Test]
    public function it_sends_an_assigned_ticket_back_to_open_without_a_technician(): void
    {
        $technician = User::factory()->create();
        $ticket = Ticket::factory()->assignedToTechnician($technician)->create();

        app(UnassignTicket::class)($ticket);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->getKey(),
            'status' => TicketStatus::Open->value,
            'assigned_technician_id' => null,
        ]);
    }

    #[Test]
    public function it_starts_progress_on_an_assigned_ticket(): void
    {
        $ticket = Ticket::factory()->assignedToTechnician()->create();

        app(StartTicketProgress::class)($ticket);

        $this->assertSame(TicketStatus::InProgress, $ticket->fresh()->status);
    }

    #[Test]
    public function it_resolves_a_ticket_in_progress_and_stamps_the_resolution(): void
    {
        Queue::fake();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::InProgress]);

        app(ResolveTicket::class)($ticket);

        $resolved = $ticket->fresh();
        $this->assertSame(TicketStatus::Resolved, $resolved->status);
        $this->assertNotNull($resolved->resolved_at);
    }

    #[Test]
    public function it_closes_a_resolved_ticket(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Resolved]);

        app(CloseTicket::class)($ticket);

        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
    }

    #[Test]
    public function it_clears_the_resolution_and_the_sla_verdict_when_a_ticket_is_reopened(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Resolved]);
        $ticket->forceFill(['resolved_at' => now()->subHour(), 'sla_met' => true])->save();

        app(ReopenTicket::class)($ticket);

        $reopened = $ticket->fresh();
        $this->assertSame(TicketStatus::InProgress, $reopened->status);
        $this->assertNull($reopened->resolved_at);
        $this->assertNull($reopened->sla_met);
    }

    #[Test]
    public function it_refuses_to_close_a_ticket_that_is_still_open(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $this->expectException(IllegalTicketTransitionException::class);

        app(CloseTicket::class)($ticket);
    }

    #[Test]
    public function it_refuses_to_assign_a_closed_ticket(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Closed]);

        $this->expectException(IllegalTicketTransitionException::class);

        app(AssignTicket::class)($ticket, User::factory()->create());
    }

    #[Test]
    public function it_leaves_the_ticket_untouched_when_a_transition_is_refused(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $technician = User::factory()->create();

        $this->assertThrows(
            fn () => app(ResolveTicket::class)($ticket),
            IllegalTicketTransitionException::class,
        );

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->getKey(),
            'status' => TicketStatus::Open->value,
            'resolved_at' => null,
        ]);
        $this->assertDatabaseMissing('tickets', [
            'id' => $ticket->getKey(),
            'assigned_technician_id' => $technician->getKey(),
        ]);
    }
}
