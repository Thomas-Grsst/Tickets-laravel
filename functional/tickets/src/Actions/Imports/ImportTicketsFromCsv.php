<?php

namespace Functional\Tickets\Actions\Imports;

use Functional\Tickets\Enums\TicketImportStatus;
use Functional\Tickets\Models\TicketImport;
use Illuminate\Support\Collection;

/**
 * Every stage is a single-purpose, independently testable transform over the same
 * collection of rows: read, normalize, validate, resolve references, create. None of them
 * know about the others.
 */
class ImportTicketsFromCsv
{
    /** @var list<class-string> */
    private const STAGES = [
        NormalizeTicketImportRows::class,
        ValidateTicketImportRows::class,
        ResolveTicketImportRequesters::class,
        CreateTicketsFromImportRows::class,
    ];

    public function __invoke(TicketImport $ticketImport, string $disk, string $path): void
    {
        $ticketImport->forceFill(['status' => TicketImportStatus::Processing])->save();

        $rows = app(ReadTicketImportCsv::class)($disk, $path);

        /** @var Collection<int, non-empty-array<string, mixed>> $rows */
        $rows = array_reduce(
            self::STAGES,
            fn (Collection $rows, string $stage): Collection => app($stage)($rows),
            $rows,
        );

        $rejections = $rows
            ->filter(fn (array $row): bool => $row['rejected'])
            ->map(fn (array $row): array => ['line' => $row['line'], 'reason' => $row['rejection_reason']])
            ->values()
            ->all();

        $ticketImport->forceFill([
            'status' => TicketImportStatus::Completed,
            'rows_read' => $rows->count(),
            'tickets_created' => $rows->count() - count($rejections),
            'rejections' => $rejections,
        ])->save();
    }
}
