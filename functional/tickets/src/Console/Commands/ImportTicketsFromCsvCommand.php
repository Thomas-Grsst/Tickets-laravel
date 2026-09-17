<?php

namespace Functional\Tickets\Console\Commands;

use Functional\Tickets\Actions\ImportTicketsFromCsv;
use Functional\Tickets\Importing\TicketImportPipeline;
use Functional\Tickets\Importing\TicketImportRejection;
use Functional\Tickets\Importing\TicketImportReport;
use Functional\Tickets\Jobs\ImportTicketsFromCsvFile;
use Illuminate\Console\Command;

/**
 * A path on disk is the whole input, so the console is the honest entry point: no upload
 * form to build, no multipart limits to fight, and the same command works from a cron
 * entry or a deployment hook. A web upload can be added later by dispatching the very same
 * job from a controller.
 *
 * The run itself belongs to the queue — the operator gets an answer immediately and the
 * import survives the terminal being closed. `--dry-run` is the exception: it runs the
 * pipeline minus its writing stage, in-process, so the rejection list can be read before
 * anything lands in the database.
 */
class ImportTicketsFromCsvCommand extends Command
{
    protected $signature = 'tickets:import-csv {path : Path to the CSV file to import} {--dry-run : Report what would be imported without creating anything}';

    protected $description = 'Queue a bulk ticket import from a CSV file, or preview its rejections with --dry-run';

    public function handle(ImportTicketsFromCsv $importTicketsFromCsv): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path) || ! is_readable($path)) {
            $this->components->error(__('tickets::messages.import.console.unreadable', ['path' => $path]));

            return self::FAILURE;
        }

        if ($this->option('dry-run') === true) {
            $this->reportImport($importTicketsFromCsv($path, TicketImportPipeline::DRY_RUN_STAGES));

            return self::SUCCESS;
        }

        ImportTicketsFromCsvFile::dispatch($path);

        $this->components->info(__('tickets::messages.import.console.queued', ['path' => $path]));

        return self::SUCCESS;
    }

    private function reportImport(TicketImportReport $report): void
    {
        if ($report->rowsRejected() > 0) {
            $this->table(
                [
                    __('tickets::messages.import.console.columns.line'),
                    __('tickets::messages.import.console.columns.reason'),
                ],
                $report->rejections
                    ->map(static fn (TicketImportRejection $rejection): array => [
                        (string) $rejection->lineNumber,
                        $rejection->reason,
                    ])
                    ->all(),
            );
        }

        $this->components->info(__('tickets::messages.import.console.summary', [
            'read' => $report->rowsRead,
            'created' => $report->ticketsCreated,
            'rejected' => $report->rowsRejected(),
        ]));
    }
}
