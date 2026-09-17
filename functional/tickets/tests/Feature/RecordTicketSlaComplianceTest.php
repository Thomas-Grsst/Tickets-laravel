<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Exceptions\TicketSlaNotMeasurableException;
use Functional\Tickets\Jobs\RecordTicketSlaCompliance;
use Functional\Tickets\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordTicketSlaComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_sla_met_when_resolved_within_the_target(): void
    {
        $ticket = Ticket::factory()->create([
            'priority' => TicketPriority::Critical,
            'status' => TicketStatus::Resolved,
            'created_at' => now()->subHour(),
            'resolved_at' => now(),
        ]);

        (new RecordTicketSlaCompliance($ticket))->handle();

        $this->assertTrue($ticket->refresh()->sla_met);
    }

    public function test_it_records_sla_missed_when_resolved_after_the_target(): void
    {
        $ticket = Ticket::factory()->create([
            'priority' => TicketPriority::Critical,
            'status' => TicketStatus::Resolved,
            'created_at' => now()->subHours(10),
            'resolved_at' => now(),
        ]);

        (new RecordTicketSlaCompliance($ticket))->handle();

        $this->assertFalse($ticket->refresh()->sla_met);
    }

    public function test_it_refuses_to_measure_a_ticket_that_is_not_resolved(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open, 'resolved_at' => null]);

        $this->expectException(TicketSlaNotMeasurableException::class);

        (new RecordTicketSlaCompliance($ticket))->handle();
    }
}
