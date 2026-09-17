<?php

namespace Functional\Tickets\Tests\Feature\Actions;

use Functional\Tickets\Actions\AssignTicket;
use Functional\Tickets\Actions\UnassignTicket;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Exceptions\IllegalTicketTransitionException;
use Functional\Tickets\Mail\TicketAssignedMail;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Notifications\TicketAssignedNotification;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssignTicketNotifiesTechnicianTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_notifies_the_technician_when_a_ticket_is_assigned_to_them(): void
    {
        Notification::fake();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $technician = User::factory()->create();

        app(AssignTicket::class)($ticket, $technician);

        Notification::assertSentTo(
            $technician,
            TicketAssignedNotification::class,
            fn (TicketAssignedNotification $notification): bool => $notification->ticket->is($ticket),
        );
    }

    #[Test]
    public function it_notifies_nobody_else_when_a_ticket_is_assigned(): void
    {
        Notification::fake();
        $requester = User::factory()->create();
        $ticket = Ticket::factory()->for($requester, 'requester')->create(['status' => TicketStatus::Open]);
        $technician = User::factory()->create();

        app(AssignTicket::class)($ticket, $technician);

        Notification::assertNotSentTo($requester, TicketAssignedNotification::class);
        Notification::assertCount(1);
    }

    #[Test]
    public function it_sends_no_notification_when_the_assignment_is_refused(): void
    {
        Notification::fake();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Closed]);
        $technician = User::factory()->create();

        $this->assertThrows(
            fn () => app(AssignTicket::class)($ticket, $technician),
            IllegalTicketTransitionException::class,
        );

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_notifies_nobody_when_a_ticket_is_unassigned(): void
    {
        Notification::fake();
        $ticket = Ticket::factory()->assignedToTechnician()->create();

        app(UnassignTicket::class)($ticket);

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_builds_the_assignment_mail_from_the_notification(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $technician = User::factory()->create();

        $mailable = (new TicketAssignedNotification($ticket))->toMail($technician);

        $this->assertInstanceOf(TicketAssignedMail::class, $mailable);
        $mailable->assertSeeInHtml($ticket->title);
    }
}
