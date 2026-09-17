<?php

namespace Functional\Tickets\Actions\Imports;

use Functional\Tickets\Enums\TicketPriority;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ValidateTicketImportRows
{
    /**
     * @param  Collection<int, non-empty-array<string, mixed>>  $rows
     * @return Collection<int, non-empty-array<string, mixed>>
     */
    public function __invoke(Collection $rows): Collection
    {
        /** @var Collection<int, non-empty-array<string, mixed>> $validated */
        $validated = $rows->map(function (array $row): array {
            if ($row['rejected']) {
                return $row;
            }

            $validator = Validator::make($row, [
                'title' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
                'priority' => ['required', Rule::enum(TicketPriority::class)],
                'requester_email' => ['required', 'email'],
            ]);

            if ($validator->fails()) {
                $row['rejected'] = true;
                $row['rejection_reason'] = $validator->errors()->first();
            }

            return $row;
        });

        return $validated;
    }
}
