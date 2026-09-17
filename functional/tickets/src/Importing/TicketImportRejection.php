<?php

namespace Functional\Tickets\Importing;

/**
 * A row the import refused, kept as a value rather than raised as an exception: bad data
 * is an expected outcome of reading someone else's spreadsheet, so it has to be
 * collectable without stopping the run. Infrastructure failures are the opposite and stay
 * exceptions — that asymmetry is the whole error model of this import.
 */
readonly class TicketImportRejection
{
    public function __construct(
        public int $lineNumber,
        public string $reason,
    ) {}

    /**
     * @return array{line_number: int, reason: string}
     */
    public function toLogContext(): array
    {
        return ['line_number' => $this->lineNumber, 'reason' => $this->reason];
    }
}
