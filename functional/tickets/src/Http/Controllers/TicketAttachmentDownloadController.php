<?php

namespace Functional\Tickets\Http\Controllers;

use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentDownloadController
{
    public function __invoke(Ticket $ticket, Attachment $attachment): StreamedResponse
    {
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        Gate::authorize('view', $attachment);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }
}
