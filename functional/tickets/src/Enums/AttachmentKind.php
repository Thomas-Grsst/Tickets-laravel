<?php

namespace Functional\Tickets\Enums;

use Functional\Tickets\Exceptions\UnsupportedAttachmentTypeException;

/**
 * The families of files a ticket accepts. Each case owns the media types it covers, so the
 * validation rules, the stored kind and the label all read from the same table.
 */
enum AttachmentKind: string
{
    case Image = 'image';
    case Document = 'document';
    case Archive = 'archive';

    /**
     * @return list<string>
     */
    public function mimeTypes(): array
    {
        return match ($this) {
            self::Image => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
            ],
            self::Document => [
                'application/pdf',
                'text/plain',
                'text/csv',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            self::Archive => [
                'application/zip',
                'application/gzip',
            ],
        };
    }

    public function label(): string
    {
        return __("tickets::messages.attachments.kind.{$this->value}");
    }

    public static function fromMimeType(?string $mimeType): self
    {
        foreach (self::cases() as $kind) {
            if (in_array($mimeType, $kind->mimeTypes(), strict: true)) {
                return $kind;
            }
        }

        throw new UnsupportedAttachmentTypeException($mimeType);
    }

    /**
     * @return list<string>
     */
    public static function acceptedMimeTypes(): array
    {
        return array_merge(...array_map(
            static fn (self $kind): array => $kind->mimeTypes(),
            self::cases(),
        ));
    }
}
