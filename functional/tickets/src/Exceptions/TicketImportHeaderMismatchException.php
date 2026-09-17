<?php

namespace Functional\Tickets\Exceptions;

use RuntimeException;

/**
 * A file-level defect rather than a row-level one: with a column missing from the header,
 * no row in the file could ever be valid, so collecting a rejection per row would just
 * restate the same fact N times. The run aborts and names the missing columns.
 */
class TicketImportHeaderMismatchException extends RuntimeException
{
    /**
     * @param  list<string>  $missingColumns
     */
    public function __construct(string $path, array $missingColumns)
    {
        parent::__construct(sprintf(
            'The ticket import file at %s is missing the column(s): %s.',
            $path,
            implode(', ', $missingColumns),
        ));
    }
}
