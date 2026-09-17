<?php

namespace Functional\Tickets\Tests\Feature\Api;

use Functional\Tickets\Enums\AttachmentKind;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Storage\AttachmentStorage;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
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
    public function it_stores_an_uploaded_file_against_the_ticket(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create();
        Sanctum::actingAs($requester);

        $this->post(
            route('tickets.attachments.store', $ticket),
            ['file' => UploadedFile::fake()->create('incident-report.pdf', 120, 'application/pdf')],
            ['Accept' => 'application/json'],
        )->assertStatus(Response::HTTP_CREATED);

        $attachment = $ticket->attachments()->sole();

        $this->assertSame('incident-report.pdf', $attachment->name);
        $this->assertSame(AttachmentKind::Document, $attachment->kind);
        $this->assertSame($requester->getKey(), $attachment->uploader->getKey());
        Storage::disk(AttachmentStorage::diskName())->assertExists($attachment->path);
    }

    #[Test]
    public function it_refuses_a_media_type_outside_the_accepted_families(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create();
        Sanctum::actingAs($requester);

        $this->post(
            route('tickets.attachments.store', $ticket),
            ['file' => UploadedFile::fake()->create('installer.exe', 10, 'application/x-msdownload')],
            ['Accept' => 'application/json'],
        )->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseCount('attachments', 0);
    }

    #[Test]
    public function it_refuses_an_upload_on_a_ticket_outside_the_perimeter(): void
    {
        $requester = $this->createRequester();
        $foreignTicket = Ticket::factory()->create();
        Sanctum::actingAs($requester);

        $this->post(
            route('tickets.attachments.store', $foreignTicket),
            ['file' => UploadedFile::fake()->image('screenshot.png')],
            ['Accept' => 'application/json'],
        )->assertForbidden();

        $this->assertDatabaseCount('attachments', 0);
    }

    #[Test]
    public function it_downloads_an_attachment_of_a_ticket_the_user_can_see(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create();
        $attachment = Attachment::factory()->for($ticket)->withStoredFile()->create(['name' => 'notes.txt']);
        Sanctum::actingAs($requester);

        $this->get(route('attachments.download', $attachment))
            ->assertSuccessful()
            ->assertDownload('notes.txt');
    }

    #[Test]
    public function it_refuses_to_download_an_attachment_of_a_ticket_outside_the_perimeter(): void
    {
        $attachment = Attachment::factory()->withStoredFile()->create();
        Sanctum::actingAs($this->createRequester());

        $this->get(route('attachments.download', $attachment))->assertForbidden();
    }

    #[Test]
    public function it_exposes_the_attachments_of_a_ticket_through_the_rest_resource(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create();
        Attachment::factory()->count(2)->for($ticket)->create();
        Sanctum::actingAs($requester);

        $this->postJson('/api/v1/tickets/search', [
            'search' => ['includes' => [['relation' => 'attachments']]],
        ])
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(2, 'data.0.attachments');
    }

    #[Test]
    public function it_deletes_the_stored_file_when_the_attachment_row_goes_away(): void
    {
        $attachment = Attachment::factory()->withStoredFile()->create();
        $disk = Storage::disk(AttachmentStorage::diskName());
        $disk->assertExists($attachment->path);

        $attachment->delete();

        $disk->assertMissing($attachment->path);
    }
}
