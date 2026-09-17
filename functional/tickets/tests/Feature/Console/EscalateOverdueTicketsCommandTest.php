<?php

namespace Functional\Tickets\Tests\Feature\Console;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Notifications\TicketEscalatedNotification;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Functional\Users\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EscalateOverdueTicketsCommandTest extends TestCase
{
    use CreatesTicketProfiles;
    use RefreshDatabase;

    private const ESCALATE_COMMAND = 'tickets:escalate';

    #[Test]
    public function it_escalates_a_ticket_past_its_priority_sla_target(): void
    {
        Notification::fake();
        $manager = $this->createManager();
        $ticket = $this->createOverdueTicket(TicketPriority::Low);

        $this->artisan(self::ESCALATE_COMMAND)->assertSuccessful();

        $this->assertSame(TicketPriority::Normal, $ticket->refresh()->priority);
        $this->assertNotNull($ticket->escalated_at);
        Notification::assertSentTo(
            $manager,
            TicketEscalatedNotification::class,
            fn (TicketEscalatedNotification $notification): bool => $notification->ticket->is($ticket)
                && $notification->breachedPriority === TicketPriority::Low,
        );
    }

    #[Test]
    public function it_leaves_a_ticket_still_inside_its_sla_window_alone(): void
    {
        Notification::fake();
        $this->createManager();
        $ticket = Ticket::factory()->create([
            'priority'   => TicketPriority::Normal,
            'status'     => TicketStatus::Open,
            'created_at' => now()->subHours(TicketPriority::Normal->slaHours() - 1),
        ]);

        $this->artisan(self::ESCALATE_COMMAND)->assertSuccessful();

        $this->assertSame(TicketPriority::Normal, $ticket->refresh()->priority);
        $this->assertNull($ticket->escalated_at);
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_does_not_escalate_the_same_breach_twice(): void
    {
        Notification::fake();
        $this->createManager();
        $ticket = $this->createOverdueTicket(TicketPriority::Low);

        $this->artisan(self::ESCALATE_COMMAND)->assertSuccessful();
        $this->artisan(self::ESCALATE_COMMAND)->assertSuccessful();

        $this->assertSame(TicketPriority::Normal, $ticket->refresh()->priority);
        Notification::assertCount(1);
    }

    #[Test]
    public function it_flags_an_overdue_critical_ticket_without_raising_it_further(): void
    {
        Notification::fake();
        $this->createManager();
        $ticket = $this->createOverdueTicket(TicketPriority::Critical);

        $this->artisan(self::ESCALATE_COMMAND)
            ->expectsOutputToContain('flagged at Critical: 1')
            ->assertSuccessful();

        $this->assertSame(TicketPriority::Critical, $ticket->refresh()->priority);
        $this->assertNull($ticket->escalated_at);
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_ignores_tickets_that_already_reached_the_end_of_the_lifecycle(): void
    {
        Notification::fake();
        $this->createManager();
        $resolved = $this->createOverdueTicket(TicketPriority::Low, TicketStatus::Resolved);
        $closed = $this->createOverdueTicket(TicketPriority::Low, TicketStatus::Closed);

        $this->artisan(self::ESCALATE_COMMAND)->assertSuccessful();

        $this->assertSame(TicketPriority::Low, $resolved->refresh()->priority);
        $this->assertSame(TicketPriority::Low, $closed->refresh()->priority);
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_runs_the_same_number_of_queries_whatever_the_ticket_count(): void
    {
        Notification::fake();
        $this->createManager();

        $this->seedOverdueTickets(1);
        $this->artisan(self::ESCALATE_COMMAND)->assertSuccessful();

        $this->seedOverdueTickets(3);
        $smallRunQueries = $this->captureEscalationQueries();

        $this->seedOverdueTickets(60);
        $largeRunQueries = $this->captureEscalationQueries();

        $this->assertSame(
            count($smallRunQueries),
            count($largeRunQueries),
            implode("\n", $largeRunQueries),
        );
    }

    private function createOverdueTicket(
        TicketPriority $priority,
        TicketStatus $status = TicketStatus::Open,
    ): Ticket {
        return Ticket::factory()->create([
            'priority'   => $priority,
            'status'     => $status,
            'created_at' => now()->subHours($priority->slaHours() + 1),
        ]);
    }

    private function seedOverdueTickets(int $count): void
    {
        $requester = User::factory()->create();

        foreach (TicketPriority::cases() as $priority) {
            Ticket::factory()
                ->count($count)
                ->for($requester, 'requester')
                ->create([
                    'priority'     => $priority,
                    'status'       => TicketStatus::Open,
                    'escalated_at' => null,
                    'created_at'   => now()->subHours($priority->slaHours() + 1),
                ]);
        }
    }

    /**
     * @return list<string>
     */
    private function captureEscalationQueries(): array
    {
        $statements = [];
        DB::listen(static function (QueryExecuted $query) use (&$statements): void {
            $statements[] = $query->sql;
        });

        $this->artisan(self::ESCALATE_COMMAND)->assertSuccessful();

        return $statements;
    }
}
