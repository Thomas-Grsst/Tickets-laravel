<?php

namespace Functional\Tickets\Actions;

use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Http\UploadedFile;

class UploadTicketAttachment
{
    private const DISK = 'local';

    private const DIRECTORY = 'ticket-attachments';

    public function __invoke(Ticket $ticket, User $uploader, UploadedFile $file): Attachment
    {
        $path = $file->store(self::DIRECTORY, self::DISK);

        return $ticket->attachments()->create([
            'uploaded_by' => $uploader->getKey(),
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }
}
