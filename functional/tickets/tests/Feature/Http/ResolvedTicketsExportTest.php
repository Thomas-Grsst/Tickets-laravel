<?php

namespace Functional\Tickets\Tests\Feature\Http;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResolvedTicketsExportTest extends TestCase
{
    use CreatesTicketProfiles;
    use RefreshDatabase;

    #[Test]
    public function it_exports_the_tickets_resolved_during_the_current_month(): void
    {
        Sanctum::actingAs($this->createManager());
        $thisMonth = Ticket::factory()->create([
            'title' => 'Resolved inside the window',
            'status' => TicketStatus::Resolved,
            'priority' => TicketPriority::High,
            'resolved_at' => now()->startOfMonth()->addDay(),
        ]);
        Ticket::factory()->create([
            'title' => 'Resolved before the window',
            'status' => TicketStatus::Resolved,
            'resolved_at' => now()->startOfMonth()->subDays(3),
        ]);

        $response = $this->get('/api/v1/tickets/exports/resolved-this-month');

        $response->assertSuccessful();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('id,title,status,priority,resolved_at', $csv);
        $this->assertStringContainsString('Resolved inside the window', $csv);
        $this->assertStringNotContainsString('Resolved before the window', $csv);
        $this->assertStringContainsString((string) $thisMonth->getKey(), $csv);
    }

    #[Test]
    public function it_names_the_export_after_the_month_it_covers(): void
    {
        Sanctum::actingAs($this->createManager());

        $this->get('/api/v1/tickets/exports/resolved-this-month')
            ->assertSuccessful()
            ->assertDownload('resolved-tickets-' . now()->format('Y-m') . '.csv');
    }

    #[Test]
    public function it_refuses_the_export_to_an_unauthenticated_caller(): void
    {
        $this->getJson('/api/v1/tickets/exports/resolved-this-month')->assertUnauthorized();
    }
}
