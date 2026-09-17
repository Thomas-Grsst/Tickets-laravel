<?php

namespace Functional\Tickets\Database\Factories;

use Functional\Tickets\Enums\AttachmentKind;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Storage\AttachmentStorage;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var AttachmentKind $kind */
        $kind = faker()->randomElement(AttachmentKind::cases());

        /** @var string $mimeType */
        $mimeType = faker()->randomElement($kind->mimeTypes());

        $createdAt = faker()->dateTime('-60 days', 'now');

        return [
            'ticket_id' => Ticket::factory(),
            'uploaded_by_id' => User::factory(),
            'name' => "{$this->basename()}.{$this->extensionFor($mimeType)}",
            'path' => AttachmentStorage::directory() . '/' . Str::random(40),
            'kind' => $kind,
            'mime_type' => $mimeType,
            'size_in_bytes' => faker()->number(1024, 2_000_000),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    /**
     * Puts real bytes behind the row so a seeded attachment is downloadable, not a dangling
     * database record.
     */
    public function withStoredFile(): static
    {
        return $this->afterCreating(function (Attachment $attachment): void {
            AttachmentStorage::disk()->put($attachment->path, faker()->paragraphs(3));
        });
    }

    private function basename(): string
    {
        return Str::slug(faker()->words(2));
    }

    private function extensionFor(string $mimeType): string
    {
        return Str::afterLast($mimeType, '/');
    }
}
