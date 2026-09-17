<?php

namespace Functional\Tickets\Database\Factories;

use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

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
        $originalName = faker()->words(2).'.txt';

        return [
            'ticket_id' => Ticket::factory(),
            'uploaded_by' => User::factory(),
            'disk' => 'local',
            'path' => 'ticket-attachments/'.faker()->uuid().'.txt',
            'original_name' => $originalName,
            'mime_type' => 'text/plain',
            'size' => faker()->number(200, 20_000),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Attachment $attachment): void {
            Storage::disk($attachment->disk)->put($attachment->path, "Attachment fixture: {$attachment->original_name}");
        });
    }
}
