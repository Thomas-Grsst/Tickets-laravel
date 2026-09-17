<?php

namespace Functional\Tickets\Importing\Stages;

use Closure;
use Functional\Tickets\Importing\TicketImportPayload;
use Functional\Tickets\Importing\TicketImportRejection;
use Functional\Tickets\Importing\TicketImportRow;
use Functional\Tickets\Importing\TicketImportStage;
use Functional\Users\Models\User;
use Illuminate\Support\Collection;

/**
 * Every referenced address is resolved in ONE query, whatever the file's size: the whole
 * batch is in memory at this point, so the distinct emails are known up front and a
 * per-row lookup would only buy a thousand round trips.
 *
 * An address nobody owns is bad data, so the row is rejected and the batch continues.
 */
class ResolveTicketImportRequesters implements TicketImportStage
{
    public function handle(TicketImportPayload $payload, Closure $next): TicketImportPayload
    {
        $requesterIdsByEmail = $this->findRequesterIds($payload->rows);

        /** @var Collection<int, TicketImportRejection> $rejections */
        $rejections = new Collection();

        $resolvedRows = $payload->rows
            ->filter(function (TicketImportRow $row) use ($requesterIdsByEmail, $rejections): bool {
                if ($requesterIdsByEmail->has($row->requesterEmail)) {
                    return true;
                }

                $rejections->push(new TicketImportRejection(
                    $row->lineNumber,
                    __('tickets::messages.import.rejection.unknown_requester', ['email' => $row->requesterEmail]),
                ));

                return false;
            })
            ->map(static fn (TicketImportRow $row): TicketImportRow => $row->withRequesterId(
                (int) $requesterIdsByEmail->get($row->requesterEmail),
            ));

        return $next($payload->withRows($resolvedRows)->withRejections($rejections));
    }

    /**
     * @param  Collection<int, TicketImportRow>  $rows
     * @return Collection<string, int>
     */
    private function findRequesterIds(Collection $rows): Collection
    {
        $emails = $rows
            ->map(static fn (TicketImportRow $row): string => $row->requesterEmail)
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return new Collection();
        }

        /** @var Collection<string, int> $requesterIds */
        $requesterIds = User::query()->whereIn('email', $emails->all())->pluck('id', 'email');

        return $requesterIds;
    }
}
