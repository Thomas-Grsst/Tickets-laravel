<?php

namespace Functional\Tickets\Importing;

use Illuminate\Support\Collection;

/**
 * What one import run did. Only ever built from a pipeline that ran to completion — a
 * technical failure throws instead, so this object can never say "0 created, all fine"
 * about an import that actually died.
 */
readonly class TicketImportReport
{
    /**
     * @param  Collection<int, TicketImportRejection>  $rejections
     */
    public function __construct(
        public string $path,
        public int $rowsRead,
        public int $ticketsCreated,
        public Collection $rejections,
    ) {}

    public static function fromPayload(TicketImportPayload $payload): self
    {
        return new self(
            $payload->path,
            $payload->readCount,
            $payload->createdCount,
            $payload->rejections->sortBy(
                static fn (TicketImportRejection $rejection): int => $rejection->lineNumber,
            )->values(),
        );
    }

    public function rowsRejected(): int
    {
        return $this->rejections->count();
    }

    /**
     * @return array<string, mixed>
     */
    public function toLogContext(): array
    {
        return [
            'path' => $this->path,
            'rows_read' => $this->rowsRead,
            'tickets_created' => $this->ticketsCreated,
            'rows_rejected' => $this->rowsRejected(),
            'rejections' => $this->rejections
                ->map(static fn (TicketImportRejection $rejection): array => $rejection->toLogContext())
                ->all(),
        ];
    }
}
