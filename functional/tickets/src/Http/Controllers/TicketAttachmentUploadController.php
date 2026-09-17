<?php

namespace Functional\Tickets\Http\Controllers;

use Functional\Tickets\Actions\StoreTicketAttachment;
use Functional\Tickets\Http\Requests\StoreAttachmentRequest;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class TicketAttachmentUploadController
{
    public function __construct(private readonly StoreTicketAttachment $storeTicketAttachment) {}

    public function __invoke(StoreAttachmentRequest $request, Ticket $ticket): JsonResponse
    {
        Gate::authorize('create', [Attachment::class, $ticket]);

        /** @var User $uploader */
        $uploader = $request->user();

        $attachment = ($this->storeTicketAttachment)($ticket, $request->uploadedFile(), $uploader);

        return response()->json(
            ['data' => $attachment->only(['id', 'name', 'kind', 'mime_type', 'size_in_bytes'])],
            Response::HTTP_CREATED,
        );
    }
}
