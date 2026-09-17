<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Importing\TicketImportPayload;
use Functional\Tickets\Importing\TicketImportPipeline;
use Functional\Tickets\Importing\TicketImportReport;
use Functional\Tickets\Importing\TicketImportStage;
use Illuminate\Pipeline\Pipeline;

/**
 * The only public entry point to the import. Callers hand over a path and get a report;
 * the stage list stays an implementation detail of the flow.
 */
class ImportTicketsFromCsv
{
    public function __construct(private Pipeline $pipeline) {}

    /**
     * @param  list<class-string<TicketImportStage>>  $stages
     */
    public function __invoke(string $path, array $stages = TicketImportPipeline::STAGES): TicketImportReport
    {
        $importedPayload = $this->pipeline
            ->send(new TicketImportPayload($path))
            ->through($stages)
            ->thenReturn();

        return TicketImportReport::fromPayload($importedPayload);
    }
}
