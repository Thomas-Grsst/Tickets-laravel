<?php

namespace Functional\Tickets\Exceptions;

use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Storage\AttachmentStorage;
use RuntimeException;

/**
 * Never a client's fault: the upload passed validation, so a refusal from the disk means
 * the filesystem itself is unavailable or full.
 */
class AttachmentStorageFailedException extends RuntimeException
{
    public function __construct(Ticket $ticket)
    {
        parent::__construct(sprintf(
            'Storing an attachment for ticket #%s on disk [%s] failed.',
            $ticket->getKey(),
            AttachmentStorage::diskName(),
        ));
    }
}
