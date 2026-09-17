<?php

namespace Functional\Tickets\Http\Controllers;

use Functional\Tickets\Actions\UploadTicketAttachment;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TicketAttachmentUploadController
{
    public function __invoke(Request $request, Ticket $ticket): JsonResponse
    {
        Gate::authorize('create', [Attachment::class, $ticket]);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,gif,txt,csv,docx,xlsx,zip'],
        ]);

        $attachment = app(UploadTicketAttachment::class)($ticket, $request->user(), $validated['file']);

        return response()->json([
            'id' => $attachment->id,
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'size' => $attachment->size,
            'created_at' => $attachment->created_at,
        ], 201);
    }
}
