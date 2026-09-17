<?php

namespace Functional\Tickets\Tests\Feature\Jobs;

use Functional\Tickets\Actions\ResolveTicket;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Exceptions\TicketSlaNotMeasurableException;
use Functional\Tickets\Jobs\RecordTicketSlaCompliance;
use Functional\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordTicketSlaComplianceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_the_sla_as_met_when_the_ticket_was_resolved_inside_its_target(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::InProgress,
            'priority' => TicketPriority::High,
            'created_at' => now()->subHours(3),
        ]);

        app(ResolveTicket::class)($ticket);

        $this->assertTrue($ticket->fresh()->sla_met);
    }

    #[Test]
    public function it_records_the_sla_as_missed_when_the_ticket_was_resolved_past_its_target(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::InProgress,
            'priority' => TicketPriority::Normal,
            'created_at' => now()->subHours(30),
        ]);

        app(ResolveTicket::class)($ticket);

        $this->assertFalse($ticket->fresh()->sla_met);
    }

    #[Test]
    public function it_measures_each_priority_against_its_own_target(): void
    {
        $lowPriority = Ticket::factory()->create([
            'status' => TicketStatus::InProgress,
            'priority' => TicketPriority::Low,
            'created_at' => now()->subHours(30),
        ]);

        app(ResolveTicket::class)($lowPriority);

        $this->assertTrue(
            $lowPriority->fresh()->sla_met,
            'Thirty hours is inside the 72-hour target of a low-priority ticket.',
        );
    }

    #[Test]
    public function it_queues_the_sla_job_when_a_ticket_is_resolved(): void
    {
        Queue::fake();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::InProgress]);

        app(ResolveTicket::class)($ticket);

        Queue::assertPushed(RecordTicketSlaCompliance::class);
    }

    #[Test]
    public function it_queues_no_sla_job_when_the_resolution_is_refused(): void
    {
        Queue::fake();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        try {
            app(ResolveTicket::class)($ticket);
        } catch (\Functional\Tickets\Exceptions\IllegalTicketTransitionException) {
            // The absence of a queued job is the assertion.
        }

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_refuses_to_measure_a_ticket_that_carries_no_resolution(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $this->expectException(TicketSlaNotMeasurableException::class);

        (new RecordTicketSlaCompliance($ticket))->handle();
    }
}
