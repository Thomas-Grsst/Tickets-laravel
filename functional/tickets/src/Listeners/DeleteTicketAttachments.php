<?php

namespace Functional\Tickets\Listeners;

use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Storage\AttachmentStorage;

/**
 * A ticket leaving for good takes its attachments with it: the foreign key is deliberately
 * not a cascade, so the children are cleared here, rows and bytes in one pass.
 */
class DeleteTicketAttachments
{
    public function __invoke(Ticket $ticket): void
    {
        $storedPaths = $ticket->attachments()->pluck('path')->all();

        if ($storedPaths === []) {
            return;
        }

        AttachmentStorage::disk()->delete($storedPaths);

        $ticket->attachments()->delete();
    }
}
