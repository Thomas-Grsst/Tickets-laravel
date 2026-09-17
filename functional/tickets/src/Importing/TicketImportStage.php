<?php

namespace Functional\Tickets\Importing;

use Closure;

interface TicketImportStage
{
    public function handle(TicketImportPayload $payload, Closure $next): TicketImportPayload;
}
