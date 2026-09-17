<?php

namespace Functional\Tickets\Http\Controllers;

use Functional\Tickets\Actions\DeleteTicketAttachment;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TicketAttachmentDestroyController
{
    public function __invoke(Ticket $ticket, Attachment $attachment): Response
    {
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        Gate::authorize('delete', $attachment);

        app(DeleteTicketAttachment::class)($attachment);

        return response()->noContent();
    }
}
