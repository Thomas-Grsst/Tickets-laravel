<?php

namespace Functional\Tickets\Tests\Feature\Livewire;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Livewire\TicketAttachments;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketAttachmentsTest extends TestCase
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

    public function test_uploading_a_file_creates_an_attachment(): void
    {
        $ticket = Ticket::factory()->for($this->requester, 'requester')->create();

        Livewire::actingAs($this->requester)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->set('file', UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'))
            ->call('upload');

        $this->assertDatabaseHas('attachments', [
            'ticket_id' => $ticket->id,
            'original_name' => 'report.pdf',
        ]);
    }

    public function test_the_uploader_can_delete_their_attachment(): void
    {
        $ticket = Ticket::factory()->for($this->requester, 'requester')->create();
        $attachment = Attachment::factory()->for($ticket)->for($this->requester, 'uploader')->create();

        Livewire::actingAs($this->requester)
            ->test(TicketAttachments::class, ['ticket' => $ticket])
            ->call('delete', $attachment->id);

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }
}
