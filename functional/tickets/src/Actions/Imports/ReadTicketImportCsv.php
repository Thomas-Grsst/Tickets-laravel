<?php

namespace Functional\Tickets\Actions\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * The only stage allowed to fail loudly: an unreadable file is a technical problem, not a
 * data problem, so it interrupts the import instead of becoming a per-row rejection.
 */
class ReadTicketImportCsv
{
    /**
     * @return Collection<int, non-empty-array<string, mixed>>
     */
    public function __invoke(string $disk, string $path): Collection
    {
        $stream = Storage::disk($disk)->readStream($path);

        if ($stream === null) {
            throw new RuntimeException("Import file [{$path}] could not be opened.");
        }

        $header = fgetcsv($stream);

        if ($header === false) {
            fclose($stream);

            return collect();
        }

        $header = array_map(trim(...), $header);
        $rows = collect();
        $line = 1;

        while (($record = fgetcsv($stream)) !== false) {
            $line++;
            $rows->push([
                'line' => $line,
                'fields' => array_combine($header, array_pad($record, count($header), null)),
                'rejected' => false,
                'rejection_reason' => null,
            ]);
        }

        fclose($stream);

        return $rows;
    }
}
