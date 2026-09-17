<?php

namespace Functional\Tickets\Http\Controllers;

use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Storage\AttachmentStorage;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentDownloadController
{
    public function __invoke(Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);

        return AttachmentStorage::disk()->download($attachment->path, $attachment->name);
    }
}
