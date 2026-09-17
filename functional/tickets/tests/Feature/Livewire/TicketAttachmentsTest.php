<?php

namespace Functional\Tickets\Tests\Feature\Livewire;

use Functional\Tickets\Livewire\TicketAttachments;
use Functional\Tickets\Livewire\TicketForm;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Storage\AttachmentStorage;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketAttachmentsTest extends TestCase
{
    use CreatesTicketProfiles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(AttachmentStorage::diskName());
    }

    #[Test]
    public function it_attaches_a_file_picked_in_the_form(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create();

        Livewire::actingAs($requester)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->set('file', UploadedFile::fake()->image('screenshot.png'))
            ->call('upload')
            ->assertHasNoErrors();

        $attachment = $ticket->attachments()->sole();

        $this->assertSame('screenshot.png', $attachment->name);
        Storage::disk(AttachmentStorage::diskName())->assertExists($attachment->path);
    }

    #[Test]
    public function it_refuses_a_file_of_an_unaccepted_media_type(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create();

        Livewire::actingAs($requester)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->set('file', UploadedFile::fake()->create('installer.exe', 10, 'application/x-msdownload'))
            ->call('upload')
            ->assertHasErrors(['file' => 'mimetypes']);

        $this->assertDatabaseCount('attachments', 0);
    }

    #[Test]
    public function it_removes_an_attachment_the_user_uploaded(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create();
        $attachment = Attachment::factory()
            ->for($ticket)
            ->withStoredFile()
            ->create(['uploaded_by_id' => $requester->getKey()]);

        Livewire::actingAs($requester)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->call('delete', $attachment->getKey());

        $this->assertDatabaseCount('attachments', 0);
        Storage::disk(AttachmentStorage::diskName())->assertMissing($attachment->path);
    }

    #[Test]
    public function it_refuses_to_remove_an_attachment_uploaded_by_someone_else(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create();
        $attachment = Attachment::factory()->for($ticket)->withStoredFile()->create();

        Livewire::actingAs($requester)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->call('delete', $attachment->getKey())
            ->assertForbidden();

        $this->assertDatabaseCount('attachments', 1);
    }

    #[Test]
    public function it_closes_the_upload_form_to_a_user_outside_the_ticket_perimeter(): void
    {
        $requester = $this->createRequester();
        $foreignTicket = Ticket::factory()->create();

        Livewire::actingAs($requester)
            ->test(TicketAttachments::class, ['ticket' => $foreignTicket])
            ->assertForbidden();
    }

    #[Test]
    public function it_shows_the_attachment_panel_inside_the_ticket_form(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create();
        Attachment::factory()->for($ticket)->create(['name' => 'badge-photo.png']);

        Livewire::actingAs($requester)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->assertSee(__('tickets::messages.attachments.title'))
            ->assertSee('badge-photo.png');
    }

    #[Test]
    public function it_lists_the_attachments_of_the_ticket(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create();
        $attachment = Attachment::factory()->for($ticket)->create(['name' => 'keyboard-layout.pdf']);

        Livewire::actingAs($requester)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->assertSee($attachment->name);
    }
}
