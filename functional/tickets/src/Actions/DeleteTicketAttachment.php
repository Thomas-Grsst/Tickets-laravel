<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Models\Attachment;
use Illuminate\Support\Facades\Storage;

class DeleteTicketAttachment
{
    public function __invoke(Attachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);

        $attachment->delete();
    }
}
