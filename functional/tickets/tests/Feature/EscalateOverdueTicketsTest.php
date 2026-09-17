<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Actions\EscalateOverdueTickets;
use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Notifications\TicketSlaBreachedNotification;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EscalateOverdueTicketsTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (TicketPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        Role::findOrCreate('manager')->syncPermissions([TicketPermission::ViewAllTickets->value]);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');
    }

    public function test_an_overdue_ticket_is_escalated_and_the_manager_notified(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'priority' => TicketPriority::Normal,
            'status' => TicketStatus::Open,
            'created_at' => now()->subHours(25),
        ]);

        $summary = (new EscalateOverdueTickets)();

        $this->assertSame(1, $summary['escalated']);
        $ticket->refresh();
        $this->assertSame(TicketPriority::High, $ticket->priority);
        $this->assertNotNull($ticket->escalated_at);

        Notification::assertSentTo($this->manager, TicketSlaBreachedNotification::class);
    }

    public function test_a_ticket_within_its_sla_is_left_alone(): void
    {
        $ticket = Ticket::factory()->create([
            'priority' => TicketPriority::Normal,
            'status' => TicketStatus::Open,
            'created_at' => now()->subHours(1),
        ]);

        $summary = (new EscalateOverdueTickets)();

        $this->assertSame(0, $summary['escalated']);
        $this->assertSame(TicketPriority::Normal, $ticket->refresh()->priority);
        $this->assertNull($ticket->escalated_at);
    }

    public function test_running_it_twice_never_escalates_the_same_ticket_again(): void
    {
        $ticket = Ticket::factory()->create([
            'priority' => TicketPriority::Low,
            'status' => TicketStatus::Open,
            'created_at' => now()->subDays(4),
        ]);

        (new EscalateOverdueTickets)();
        $firstEscalation = $ticket->refresh()->escalated_at;

        $summary = (new EscalateOverdueTickets)();

        $this->assertSame(0, $summary['escalated']);
        $this->assertSame(TicketPriority::Normal, $ticket->refresh()->priority);
        $this->assertTrue($firstEscalation->equalTo($ticket->escalated_at));
    }

    public function test_an_already_critical_overdue_ticket_stays_critical_but_is_still_signalled(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'priority' => TicketPriority::Critical,
            'status' => TicketStatus::Open,
            'created_at' => now()->subHours(3),
        ]);

        $summary = (new EscalateOverdueTickets)();

        $this->assertSame(1, $summary['escalated']);
        $this->assertSame(TicketPriority::Critical, $ticket->refresh()->priority);
        $this->assertNull($ticket->escalated_at);

        Notification::assertSentTo($this->manager, TicketSlaBreachedNotification::class);
    }

    public function test_resolved_tickets_are_never_escalated(): void
    {
        $ticket = Ticket::factory()->create([
            'priority' => TicketPriority::Normal,
            'status' => TicketStatus::Resolved,
            'created_at' => now()->subDays(3),
            'resolved_at' => now(),
        ]);

        (new EscalateOverdueTickets)();

        $this->assertSame(TicketPriority::Normal, $ticket->refresh()->priority);
    }
}
