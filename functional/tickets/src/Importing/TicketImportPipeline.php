<?php

namespace Functional\Tickets\Importing;

use Functional\Tickets\Importing\Stages\CreateTicketsFromImportRows;
use Functional\Tickets\Importing\Stages\NormalizeTicketImportRows;
use Functional\Tickets\Importing\Stages\ReadTicketImportCsv;
use Functional\Tickets\Importing\Stages\ResolveTicketImportRequesters;
use Functional\Tickets\Importing\Stages\ValidateTicketImportRows;

/**
 * The flow, readable top to bottom. Adding a step is a class plus a line here.
 */
class TicketImportPipeline
{
    /** @var list<class-string<TicketImportStage>> */
    public const STAGES = [
        ReadTicketImportCsv::class,
        NormalizeTicketImportRows::class,
        ValidateTicketImportRows::class,
        ResolveTicketImportRequesters::class,
        CreateTicketsFromImportRows::class,
    ];

    /**
     * Everything except the write, for an operator who wants the rejection list before
     * anything lands in the database.
     *
     * @var list<class-string<TicketImportStage>>
     */
    public const DRY_RUN_STAGES = [
        ReadTicketImportCsv::class,
        NormalizeTicketImportRows::class,
        ValidateTicketImportRows::class,
        ResolveTicketImportRequesters::class,
    ];
}
