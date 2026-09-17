<?php

namespace Functional\Tickets\Tests\Feature\Broadcasting;

use Functional\Tickets\Actions\AssignTicket;
use Functional\Tickets\Actions\CloseTicket;
use Functional\Tickets\Actions\ReopenTicket;
use Functional\Tickets\Actions\ResolveTicket;
use Functional\Tickets\Actions\StartTicketProgress;
use Functional\Tickets\Actions\UnassignTicket;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Events\TicketAssigned;
use Functional\Tickets\Events\TicketStatusChanged;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Functional\Users\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketBroadcastsTest extends TestCase
{
    use CreatesTicketProfiles;
    use RefreshDatabase;

    #[Test]
    public function it_announces_a_status_change_for_every_transition_action(): void
    {
        Event::fake([TicketStatusChanged::class]);

        $technician = $this->createTechnician();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        app(AssignTicket::class)($ticket, $technician);
        app(UnassignTicket::class)($ticket);
        app(AssignTicket::class)($ticket, $technician);
        app(StartTicketProgress::class)($ticket);
        app(ResolveTicket::class)($ticket);
        app(CloseTicket::class)($ticket);

        Event::assertDispatchedTimes(TicketStatusChanged::class, 6);
    }

    #[Test]
    public function it_announces_a_status_change_carrying_both_sides_of_the_move(): void
    {
        Event::fake([TicketStatusChanged::class]);

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Assigned]);

        app(StartTicketProgress::class)($ticket);

        Event::assertDispatched(
            TicketStatusChanged::class,
            fn (TicketStatusChanged $event): bool => $event->ticket->is($ticket)
                && $event->previousStatus === TicketStatus::Assigned
                && $event->currentStatus === TicketStatus::InProgress,
        );
    }

    #[Test]
    public function it_announces_a_reopening_as_a_status_change(): void
    {
        Event::fake([TicketStatusChanged::class]);

        $ticket = Ticket::factory()->create(['status' => TicketStatus::Resolved]);

        app(ReopenTicket::class)($ticket);

        Event::assertDispatched(
            TicketStatusChanged::class,
            fn (TicketStatusChanged $event): bool => $event->previousStatus === TicketStatus::Resolved
                && $event->currentStatus === TicketStatus::InProgress,
        );
    }

    #[Test]
    public function it_broadcasts_a_status_change_on_the_private_channel_of_its_ticket(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $event = new TicketStatusChanged($ticket, TicketStatus::Open, TicketStatus::Assigned);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertInstanceOf(PrivateChannel::class, $event->broadcastOn());
        $this->assertSame('private-tickets.' . $ticket->getKey(), $event->broadcastOn()->name);
        $this->assertSame('ticket.status-changed', $event->broadcastAs());
    }

    #[Test]
    public function it_broadcasts_an_assignment_on_the_private_channel_of_its_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $technician = $this->createTechnician();

        $event = new TicketAssigned($ticket, $technician);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertInstanceOf(PrivateChannel::class, $event->broadcastOn());
        $this->assertSame('private-tickets.' . $ticket->getKey(), $event->broadcastOn()->name);
        $this->assertSame('ticket.assigned', $event->broadcastAs());
    }

    #[Test]
    public function it_broadcasts_only_the_fields_the_list_needs(): void
    {
        $requester = User::factory()->create(['email' => 'private@xefi.test']);
        $ticket = Ticket::factory()->for($requester, 'requester')->create([
            'status' => TicketStatus::Assigned,
            'description' => 'Sensitive internal detail.',
        ]);

        $statusChange = new TicketStatusChanged($ticket, TicketStatus::Open, TicketStatus::Assigned);

        $this->assertSame([
            'id' => $ticket->getKey(),
            'status' => TicketStatus::Assigned->value,
        ], $statusChange->broadcastWith());

        $technician = $this->createTechnician();
        $assignment = new TicketAssigned($ticket, $technician);

        $this->assertSame([
            'id' => $ticket->getKey(),
            'assigned_technician_id' => $technician->getKey(),
        ], $assignment->broadcastWith());
    }
}
