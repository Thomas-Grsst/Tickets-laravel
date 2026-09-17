<?php

namespace Functional\Tickets\Jobs;

use Functional\Tickets\Actions\Imports\ImportTicketsFromCsv;
use Functional\Tickets\Enums\TicketImportStatus;
use Functional\Tickets\Models\TicketImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessTicketImport implements ShouldQueue
{
    use Queueable;

    public function __construct(private TicketImport $ticketImport, private string $disk, private string $path) {}

    public function handle(ImportTicketsFromCsv $importTicketsFromCsv): void
    {
        $importTicketsFromCsv($this->ticketImport, $this->disk, $this->path);
    }

    public function failed(Throwable $exception): void
    {
        $this->ticketImport->forceFill(['status' => TicketImportStatus::Failed])->save();
    }
}
