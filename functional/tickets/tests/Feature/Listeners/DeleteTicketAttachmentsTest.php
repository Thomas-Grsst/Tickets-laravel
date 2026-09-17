<?php

namespace Functional\Tickets\Tests\Feature\Listeners;

use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Storage\AttachmentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteTicketAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(AttachmentStorage::diskName());
    }

    #[Test]
    public function it_clears_the_attachments_of_a_ticket_deleted_for_good(): void
    {
        $ticket = Ticket::factory()->create();
        $attachment = Attachment::factory()->for($ticket)->withStoredFile()->create();

        $ticket->forceDelete();

        $this->assertDatabaseCount('attachments', 0);
        Storage::disk(AttachmentStorage::diskName())->assertMissing($attachment->path);
    }

    #[Test]
    public function it_leaves_the_attachments_alone_while_the_ticket_is_only_soft_deleted(): void
    {
        $ticket = Ticket::factory()->create();
        $attachment = Attachment::factory()->for($ticket)->withStoredFile()->create();

        $ticket->delete();

        $this->assertDatabaseCount('attachments', 1);
        Storage::disk(AttachmentStorage::diskName())->assertExists($attachment->path);
    }

    #[Test]
    public function it_prunes_a_long_deleted_ticket_together_with_its_attachments(): void
    {
        $ticket = Ticket::factory()->create(['deleted_at' => now()->subDays(120)]);
        $attachment = Attachment::factory()->for($ticket)->withStoredFile()->create();

        (new Ticket())->pruneAll();

        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('attachments', 0);
        Storage::disk(AttachmentStorage::diskName())->assertMissing($attachment->path);
    }

    #[Test]
    public function it_deletes_a_ticket_without_attachments_without_touching_the_disk(): void
    {
        $ticket = Ticket::factory()->create();

        $ticket->forceDelete();

        $this->assertDatabaseCount('tickets', 0);
    }
}
