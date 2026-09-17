<?php

namespace Functional\Tickets\Importing\Stages;

use Closure;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Exceptions\TicketImportRowNotResolvedException;
use Functional\Tickets\Importing\TicketImportPayload;
use Functional\Tickets\Importing\TicketImportRow;
use Functional\Tickets\Importing\TicketImportStage;
use Functional\Tickets\Models\Ticket;

/**
 * Only clean, resolved rows reach this stage, so a failure here is never the spreadsheet's
 * fault — it is the database refusing to work. Nothing is caught: a QueryException travels
 * out of the pipeline and fails the job, which is the only way a lost connection cannot be
 * reported as "nothing to create, all fine".
 *
 * Each ticket is one INSERT and therefore its own atomic unit; no explicit transaction
 * wraps the batch, so an abort halfway through keeps the tickets already created instead
 * of discarding an hour of work.
 */
class CreateTicketsFromImportRows implements TicketImportStage
{
    public function handle(TicketImportPayload $payload, Closure $next): TicketImportPayload
    {
        $createdCount = 0;

        foreach ($payload->rows as $row) {
            $this->createTicket($row);
            $createdCount++;
        }

        return $next($payload->withCreatedCount($createdCount));
    }

    private function createTicket(TicketImportRow $row): void
    {
        $requesterId = $row->requesterId ?? throw new TicketImportRowNotResolvedException($row->lineNumber);

        Ticket::query()->create([
            'requester_id' => $requesterId,
            'title' => $row->title,
            'description' => $row->description,
            'priority' => TicketPriority::from($row->priority),
        ]);
    }
}
