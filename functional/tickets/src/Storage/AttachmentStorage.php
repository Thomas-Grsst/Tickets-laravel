<?php

namespace Functional\Tickets\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * The single place that knows where attachment bytes live, so the upload, the download, the
 * cleanup and the factory cannot point at different disks.
 */
class AttachmentStorage
{
    public static function diskName(): string
    {
        return (string) config('tickets.attachments.disk');
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::diskName());
    }

    public static function directory(): string
    {
        return (string) config('tickets.attachments.directory');
    }
}
