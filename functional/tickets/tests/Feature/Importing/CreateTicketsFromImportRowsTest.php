<?php

namespace Functional\Tickets\Tests\Feature\Importing;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Exceptions\TicketImportRowNotResolvedException;
use Functional\Tickets\Importing\Stages\CreateTicketsFromImportRows;
use Functional\Tickets\Importing\TicketImportPayload;
use Functional\Tickets\Importing\TicketImportRow;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateTicketsFromImportRowsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_one_open_ticket_per_resolved_row(): void
    {
        $requester = User::factory()->create();

        $payload = $this->create([
            $this->resolvedRow(2, $requester->getKey(), 'Printer down', 'high'),
            $this->resolvedRow(3, $requester->getKey(), 'VPN down', 'low'),
        ]);

        $this->assertSame(2, $payload->createdCount);
        $this->assertSame(2, Ticket::query()->count());

        $ticket = Ticket::query()->where('title', 'Printer down')->sole();

        $this->assertSame($requester->getKey(), $ticket->requester_id);
        $this->assertSame(TicketPriority::High, $ticket->priority);
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertNull($ticket->assigned_technician_id);
    }

    #[Test]
    public function it_creates_nothing_and_counts_nothing_for_an_empty_batch(): void
    {
        $payload = $this->create([]);

        $this->assertSame(0, $payload->createdCount);
        $this->assertSame(0, Ticket::query()->count());
    }

    #[Test]
    public function it_refuses_a_row_that_reached_it_without_a_resolved_requester(): void
    {
        $this->expectException(TicketImportRowNotResolvedException::class);

        $this->create([new TicketImportRow(4, 'first@example.test', 'Printer down', 'Broken', 'normal')]);
    }

    private function resolvedRow(int $lineNumber, int $requesterId, string $title, string $priority): TicketImportRow
    {
        return (new TicketImportRow($lineNumber, 'first@example.test', $title, 'Nothing prints', $priority))
            ->withRequesterId($requesterId);
    }

    /**
     * @param  list<TicketImportRow>  $rows
     */
    private function create(array $rows): TicketImportPayload
    {
        return app(CreateTicketsFromImportRows::class)->handle(
            new TicketImportPayload('irrelevant.csv', new Collection($rows)),
            static fn (TicketImportPayload $passed): TicketImportPayload => $passed,
        );
    }
}
