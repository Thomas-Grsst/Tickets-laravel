<?php

namespace Functional\Tickets\Jobs;

use Functional\Tickets\Actions\ImportTicketsFromCsv;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * The import reads a file and writes many rows, so it never runs inside the request or the
 * console process that asked for it. Unlike the SLA job it depends on no prior write, so
 * there is nothing to dispatch `afterCommit()` for — the file is the only input.
 *
 * Nothing here catches: a rejected row is already a value inside the report, so the only
 * throwable that can reach this method is a technical failure, and letting it out is what
 * moves the job to `failed_jobs` instead of logging a falsely successful report.
 */
class ImportTicketsFromCsvFile implements ShouldQueue
{
    use Queueable;

    public function __construct(private string $path) {}

    public function handle(ImportTicketsFromCsv $importTicketsFromCsv): void
    {
        $report = $importTicketsFromCsv($this->path);

        Log::info('Ticket CSV import finished.', $report->toLogContext());
    }
}
