<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResolvedTicketsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_streams_a_csv_of_tickets_resolved_this_month(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $inMonth = Ticket::factory()->create([
            'status' => TicketStatus::Resolved,
            'resolved_at' => now()->startOfMonth()->addDay(),
        ]);

        Ticket::factory()->create([
            'status' => TicketStatus::Resolved,
            'resolved_at' => now()->subMonths(2),
        ]);

        $response = $this->get('/api/v1/tickets/exports/resolved-this-month');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('id,title,status,priority,resolved_at', $csv);
        $this->assertStringContainsString((string) $inMonth->id, $csv);
    }
}
