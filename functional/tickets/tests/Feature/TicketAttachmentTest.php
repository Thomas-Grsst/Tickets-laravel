<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        foreach (TicketPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        Role::findOrCreate('requester')->syncPermissions([
            TicketPermission::CreateTicket->value,
            TicketPermission::ViewOwnTickets->value,
        ]);

        $this->requester = User::factory()->create();
        $this->requester->assignRole('requester');
    }

    public function test_the_requester_can_upload_a_file_to_their_own_ticket(): void
    {
        Sanctum::actingAs($this->requester);

        $ticket = Ticket::factory()->for($this->requester, 'requester')->create();
        $file = UploadedFile::fake()->create('screenshot.png', 500, 'image/png');

        $response = $this->post("/api/v1/tickets/{$ticket->id}/attachments", ['file' => $file]);

        $response->assertCreated();
        $response->assertJsonPath('original_name', 'screenshot.png');

        $this->assertDatabaseHas('attachments', [
            'ticket_id' => $ticket->id,
            'uploaded_by' => $this->requester->id,
            'original_name' => 'screenshot.png',
        ]);

        Storage::disk('local')->assertExists(Attachment::first()->path);
    }

    public function test_a_stranger_cannot_upload_a_file_to_someone_elses_ticket(): void
    {
        $other = User::factory()->create();
        $other->assignRole('requester');
        Sanctum::actingAs($other);

        $ticket = Ticket::factory()->for($this->requester, 'requester')->create();
        $file = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');

        $response = $this->post("/api/v1/tickets/{$ticket->id}/attachments", ['file' => $file]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('attachments', ['ticket_id' => $ticket->id]);
    }

    public function test_the_upload_rejects_a_file_that_is_too_large(): void
    {
        Sanctum::actingAs($this->requester);

        $ticket = Ticket::factory()->for($this->requester, 'requester')->create();
        $file = UploadedFile::fake()->create('huge.zip', 20_000);

        $response = $this->post("/api/v1/tickets/{$ticket->id}/attachments", ['file' => $file]);

        $response->assertStatus(422);
    }

    public function test_the_owner_can_download_their_attachment(): void
    {
        Sanctum::actingAs($this->requester);

        $ticket = Ticket::factory()->for($this->requester, 'requester')->create();
        $attachment = Attachment::factory()->for($ticket)->for($this->requester, 'uploader')->create();

        $response = $this->get("/api/v1/tickets/{$ticket->id}/attachments/{$attachment->id}/download");

        $response->assertOk();
    }

    public function test_a_stranger_cannot_download_someone_elses_attachment(): void
    {
        $other = User::factory()->create();
        $other->assignRole('requester');

        $ticket = Ticket::factory()->for($this->requester, 'requester')->create();
        $attachment = Attachment::factory()->for($ticket)->for($this->requester, 'uploader')->create();

        Sanctum::actingAs($other);

        $response = $this->get("/api/v1/tickets/{$ticket->id}/attachments/{$attachment->id}/download");

        $response->assertForbidden();
    }

    public function test_the_uploader_can_delete_their_own_attachment(): void
    {
        Sanctum::actingAs($this->requester);

        $ticket = Ticket::factory()->for($this->requester, 'requester')->create();
        $attachment = Attachment::factory()->for($ticket)->for($this->requester, 'uploader')->create();

        $response = $this->delete("/api/v1/tickets/{$ticket->id}/attachments/{$attachment->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($attachment->path);
    }

    public function test_someone_else_cannot_delete_a_colleagues_attachment(): void
    {
        $other = User::factory()->create();
        $other->assignRole('requester');

        $ticket = Ticket::factory()->for($this->requester, 'requester')->create();
        $attachment = Attachment::factory()->for($ticket)->for($this->requester, 'uploader')->create();

        Sanctum::actingAs($other);

        $response = $this->delete("/api/v1/tickets/{$ticket->id}/attachments/{$attachment->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);
    }
}
