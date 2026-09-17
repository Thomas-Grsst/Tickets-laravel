<?php

namespace Functional\Tickets\Actions\Imports;

use Illuminate\Support\Collection;

class NormalizeTicketImportRows
{
    /**
     * @param  Collection<int, non-empty-array<string, mixed>>  $rows
     * @return Collection<int, non-empty-array<string, mixed>>
     */
    public function __invoke(Collection $rows): Collection
    {
        /** @var Collection<int, non-empty-array<string, mixed>> $normalized */
        $normalized = $rows->map(function (array $row): array {
            $fields = $row['fields'];

            $row['title'] = isset($fields['title']) ? trim((string) $fields['title']) : null;
            $row['description'] = isset($fields['description']) ? trim((string) $fields['description']) : null;
            $row['priority'] = isset($fields['priority']) ? strtolower(trim((string) $fields['priority'])) : null;
            $row['requester_email'] = isset($fields['requester_email']) ? strtolower(trim((string) $fields['requester_email'])) : null;

            return $row;
        });

        return $normalized;
    }
}
