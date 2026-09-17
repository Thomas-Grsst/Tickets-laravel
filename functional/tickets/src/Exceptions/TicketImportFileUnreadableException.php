<?php

namespace Functional\Tickets\Exceptions;

use RuntimeException;

/**
 * Infrastructure, not data: the operator pointed the import at something the process
 * cannot open. Nothing about the file's contents is known yet, so there is no row to
 * reject and the run aborts.
 */
class TicketImportFileUnreadableException extends RuntimeException
{
    public function __construct(string $path)
    {
        parent::__construct(sprintf('The ticket import file at %s cannot be read.', $path));
    }
}
