<?php

namespace Functional\Tickets\Tests\Unit\Enums;

use Functional\Tickets\Enums\AttachmentKind;
use Functional\Tickets\Exceptions\UnsupportedAttachmentTypeException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttachmentKindTest extends TestCase
{
    #[Test]
    public function it_maps_a_media_type_to_the_family_that_covers_it(): void
    {
        $this->assertSame(AttachmentKind::Image, AttachmentKind::fromMimeType('image/png'));
        $this->assertSame(AttachmentKind::Document, AttachmentKind::fromMimeType('application/pdf'));
        $this->assertSame(AttachmentKind::Archive, AttachmentKind::fromMimeType('application/zip'));
    }

    #[Test]
    public function it_refuses_a_media_type_no_family_covers(): void
    {
        $this->expectException(UnsupportedAttachmentTypeException::class);

        AttachmentKind::fromMimeType('application/x-msdownload');
    }

    #[Test]
    public function it_refuses_a_file_whose_media_type_could_not_be_read(): void
    {
        $this->expectException(UnsupportedAttachmentTypeException::class);

        AttachmentKind::fromMimeType(null);
    }

    #[Test]
    public function it_gathers_the_media_types_of_every_family(): void
    {
        $acceptedMimeTypes = AttachmentKind::acceptedMimeTypes();

        foreach (AttachmentKind::cases() as $kind) {
            foreach ($kind->mimeTypes() as $mimeType) {
                $this->assertContains($mimeType, $acceptedMimeTypes);
            }
        }
    }

    #[Test]
    public function it_labels_every_family(): void
    {
        foreach (AttachmentKind::cases() as $kind) {
            $this->assertNotSame("tickets::messages.attachments.kind.{$kind->value}", $kind->label());
        }
    }
}
