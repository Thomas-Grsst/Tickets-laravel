<?php

namespace Functional\Tickets\Validation;

use Functional\Tickets\Enums\AttachmentKind;

/**
 * The single description of an acceptable upload, shared by the HTTP request and the
 * Livewire form so the two entry points cannot drift apart.
 */
class AttachmentFileRules
{
    /**
     * @return list<string>
     */
    public static function forUploadedFile(): array
    {
        $maxSizeInKilobytes = config('tickets.attachments.max_size_in_kilobytes');
        $acceptedMimeTypes = implode(',', AttachmentKind::acceptedMimeTypes());

        return [
            'required',
            'file',
            "max:{$maxSizeInKilobytes}",
            "mimetypes:{$acceptedMimeTypes}",
        ];
    }
}
