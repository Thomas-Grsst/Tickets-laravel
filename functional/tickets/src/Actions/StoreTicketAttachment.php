<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Enums\AttachmentKind;
use Functional\Tickets\Exceptions\AttachmentStorageFailedException;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Storage\AttachmentStorage;
use Functional\Users\Models\User;
use Illuminate\Http\UploadedFile;

class StoreTicketAttachment
{
    public function __invoke(Ticket $ticket, UploadedFile $file, User $uploader): Attachment
    {
        $kind = AttachmentKind::fromMimeType($file->getMimeType());

        $storedPath = AttachmentStorage::disk()->putFile(AttachmentStorage::directory(), $file);

        if (! is_string($storedPath)) {
            throw new AttachmentStorageFailedException($ticket);
        }

        return $ticket->attachments()->create([
            'uploaded_by_id' => $uploader->getKey(),
            'name' => $file->getClientOriginalName(),
            'path' => $storedPath,
            'kind' => $kind,
            'mime_type' => $file->getMimeType(),
            'size_in_bytes' => $file->getSize(),
        ]);
    }
}
