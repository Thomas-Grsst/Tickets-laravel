<?php

namespace Functional\Tickets\Importing\Stages;

use Closure;
use Functional\Tickets\Exceptions\TicketImportFileUnreadableException;
use Functional\Tickets\Exceptions\TicketImportHeaderMismatchException;
use Functional\Tickets\Importing\TicketImportPayload;
use Functional\Tickets\Importing\TicketImportRejection;
use Functional\Tickets\Importing\TicketImportRow;
use Functional\Tickets\Importing\TicketImportStage;
use Illuminate\Support\Collection;

/**
 * Turns the file into rows, and nothing else — no trimming, no validation. A record whose
 * column count disagrees with the header is bad data and becomes a rejection; a file that
 * cannot be opened, or whose header is missing a column, aborts the run because no row in
 * it could ever be valid.
 */
class ReadTicketImportCsv implements TicketImportStage
{
    public function handle(TicketImportPayload $payload, Closure $next): TicketImportPayload
    {
        $csvStream = $this->openFile($payload->path);
        $header = $this->readHeader($csvStream, $payload->path);
        $records = $this->collectRecords($csvStream, $header);

        fclose($csvStream);

        /** @var Collection<int, TicketImportRow> $rows */
        /** @var Collection<int, TicketImportRejection> $rejections */
        [$rows, $rejections] = $records->partition(
            static fn (TicketImportRow|TicketImportRejection $record): bool => $record instanceof TicketImportRow,
        );

        return $next(
            $payload
                ->withRows($rows)
                ->withRejections($rejections)
                ->withReadCount($records->count()),
        );
    }

    /**
     * @param  resource  $csvStream
     * @param  list<string>  $header
     * @return Collection<int, TicketImportRow|TicketImportRejection>
     */
    private function collectRecords($csvStream, array $header): Collection
    {
        /** @var Collection<int, TicketImportRow|TicketImportRejection> $records */
        $records = new Collection();
        $lineNumber = 1;

        while (($record = $this->readRecord($csvStream)) !== null) {
            $lineNumber++;

            if ($this->isBlank($record)) {
                continue;
            }

            $records->push(count($record) === count($header)
                ? TicketImportRow::fromColumns($lineNumber, array_combine($header, array_map(
                    static fn (?string $column): string => (string) $column,
                    $record,
                )))
                : new TicketImportRejection($lineNumber, __('tickets::messages.import.rejection.column_count', [
                    'expected' => count($header),
                    'found' => count($record),
                ])));
        }

        return $records;
    }

    /**
     * @return resource
     */
    private function openFile(string $path)
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new TicketImportFileUnreadableException($path);
        }

        $csvStream = fopen($path, 'rb');

        if ($csvStream === false) {
            throw new TicketImportFileUnreadableException($path);
        }

        return $csvStream;
    }

    /**
     * @param  resource  $csvStream
     * @return list<string>
     */
    private function readHeader($csvStream, string $path): array
    {
        $record = $this->readRecord($csvStream);

        if ($record === null) {
            throw new TicketImportHeaderMismatchException($path, TicketImportRow::COLUMNS);
        }

        $header = array_map(static fn (?string $column): string => strtolower(trim((string) $column)), $record);
        $missingColumns = array_values(array_diff(TicketImportRow::COLUMNS, $header));

        if ($missingColumns !== []) {
            throw new TicketImportHeaderMismatchException($path, $missingColumns);
        }

        return $header;
    }

    /**
     * @param  resource  $csvStream
     * @return ?list<?string>
     */
    private function readRecord($csvStream): ?array
    {
        $record = fgetcsv($csvStream, escape: '');

        return $record === false ? null : $record;
    }

    /**
     * @param  list<?string>  $record
     */
    private function isBlank(array $record): bool
    {
        return $record === [null] || $record === [''];
    }
}
