<?php

namespace Functional\Tickets\Listeners;

use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Storage\AttachmentStorage;

/**
 * The row and the bytes are deleted together: without this the disk would keep growing with
 * files no ticket points at any more.
 */
class DeleteStoredAttachmentFile
{
    public function __invoke(Attachment $attachment): void
    {
        AttachmentStorage::disk()->delete($attachment->path);
    }
}
