<?php

namespace Functional\Tickets\Actions\Imports;

use Functional\Users\Models\User;
use Illuminate\Support\Collection;

/**
 * One query for every distinct requester email in the batch — a thousand-row file still
 * resolves its requesters in a single round trip, not a thousand.
 */
class ResolveTicketImportRequesters
{
    /**
     * @param  Collection<int, non-empty-array<string, mixed>>  $rows
     * @return Collection<int, non-empty-array<string, mixed>>
     */
    public function __invoke(Collection $rows): Collection
    {
        $emails = $rows
            ->reject(fn (array $row): bool => $row['rejected'])
            ->pluck('requester_email')
            ->unique()
            ->values();

        $requesterIdsByEmail = User::query()
            ->whereIn('email', $emails)
            ->pluck('id', 'email');

        /** @var Collection<int, non-empty-array<string, mixed>> $resolved */
        $resolved = $rows->map(function (array $row) use ($requesterIdsByEmail): array {
            if ($row['rejected']) {
                return $row;
            }

            $requesterId = $requesterIdsByEmail->get($row['requester_email']);

            if ($requesterId === null) {
                $row['rejected'] = true;
                $row['rejection_reason'] = "No user found for requester_email \"{$row['requester_email']}\".";

                return $row;
            }

            $row['requester_id'] = $requesterId;

            return $row;
        });

        return $resolved;
    }
}
