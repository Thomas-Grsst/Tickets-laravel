<?php

namespace Functional\Tickets\Importing\Stages;

use Closure;
use Functional\Tickets\Importing\TicketImportPayload;
use Functional\Tickets\Importing\TicketImportRow;
use Functional\Tickets\Importing\TicketImportStage;

/**
 * Cleans what a spreadsheet reliably produces — padding spaces, a capitalised address, a
 * priority typed as "High" — before anything judges the row. Normalizing here rather than
 * inside the validator keeps the rejection reasons about the data and not about casing.
 */
class NormalizeTicketImportRows implements TicketImportStage
{
    public function handle(TicketImportPayload $payload, Closure $next): TicketImportPayload
    {
        $normalized = $payload->rows->map(
            static fn (TicketImportRow $row): TicketImportRow => $row->withNormalizedColumns(
                strtolower(trim($row->requesterEmail)),
                trim($row->title),
                trim($row->description),
                strtolower(trim($row->priority)),
            ),
        );

        return $next($payload->withRows($normalized));
    }
}
