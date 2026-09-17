<?php

namespace Functional\Tickets\Exceptions;

use RuntimeException;

/**
 * Never a spreadsheet's fault: a row only reaches the creation stage once the resolution
 * stage has attached a requester id, so an unresolved row there means the stage list was
 * reordered or shortened. It aborts rather than silently skipping a ticket.
 */
class TicketImportRowNotResolvedException extends RuntimeException
{
    public function __construct(int $lineNumber)
    {
        parent::__construct(sprintf(
            'Row %d reached ticket creation without a resolved requester id.',
            $lineNumber,
        ));
    }
}
