<?php

namespace Functional\Tickets\Importing\Stages;

use Closure;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Importing\TicketImportPayload;
use Functional\Tickets\Importing\TicketImportRejection;
use Functional\Tickets\Importing\TicketImportRow;
use Functional\Tickets\Importing\TicketImportStage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Judges each row on its own. A failure here is a *value* — a rejection pushed onto the
 * payload — never an exception, because one malformed address in a thousand rows is an
 * expected outcome of reading someone else's file, not a reason to abandon the other 999.
 */
class ValidateTicketImportRows implements TicketImportStage
{
    public function handle(TicketImportPayload $payload, Closure $next): TicketImportPayload
    {
        /** @var Collection<int, TicketImportRejection> $rejections */
        $rejections = new Collection();
        $rules = $this->rules();

        $validRows = $payload->rows->filter(function (TicketImportRow $row) use ($rejections, $rules): bool {
            $validator = Validator::make($row->toValidatableColumns(), $rules);

            if ($validator->passes()) {
                return true;
            }

            $rejections->push(new TicketImportRejection(
                $row->lineNumber,
                implode(' ', $validator->errors()->all()),
            ));

            return false;
        });

        return $next($payload->withRows($validRows)->withRejections($rejections));
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function rules(): array
    {
        return [
            TicketImportRow::COLUMN_REQUESTER_EMAIL => ['required', 'email'],
            TicketImportRow::COLUMN_TITLE => ['required', 'string', 'max:255'],
            TicketImportRow::COLUMN_DESCRIPTION => ['required', 'string'],
            TicketImportRow::COLUMN_PRIORITY => ['required', Rule::enum(TicketPriority::class)],
        ];
    }
}
