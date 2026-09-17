<?php

namespace Functional\Tickets\Actions\Imports;

use Functional\Tickets\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * All-or-nothing on purpose: the rows reaching this stage already passed validation and
 * requester resolution, so the only way this can still fail is a technical one (a dropped
 * connection, a constraint nobody expected) — and that should roll every one of them back,
 * not leave half the batch committed.
 */
class CreateTicketsFromImportRows
{
    /**
     * @param  Collection<int, non-empty-array<string, mixed>>  $rows
     * @return Collection<int, non-empty-array<string, mixed>>
     */
    public function __invoke(Collection $rows): Collection
    {
        $acceptedRows = $rows->reject(fn (array $row): bool => $row['rejected']);

        DB::transaction(function () use ($acceptedRows): void {
            foreach ($acceptedRows as $row) {
                Ticket::create([
                    'requester_id' => $row['requester_id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'priority' => $row['priority'],
                ]);
            }
        });

        return $rows;
    }
}
