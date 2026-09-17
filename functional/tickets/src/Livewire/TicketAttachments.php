<?php

namespace Functional\Tickets\Livewire;

use Functional\Tickets\Actions\DeleteTicketAttachment;
use Functional\Tickets\Actions\UploadTicketAttachment;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class TicketAttachments extends Component
{
    use WithFileUploads;

    public Ticket $ticket;

    public mixed $file = null;

    public function mount(Ticket $ticket): void
    {
        $this->authorize('view', $ticket);
        $this->ticket = $ticket;
    }

    public function upload(): void
    {
        $this->authorize('create', [Attachment::class, $this->ticket]);

        $this->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,gif,txt,csv,docx,xlsx,zip'],
        ]);

        app(UploadTicketAttachment::class)($this->ticket, auth()->user(), $this->file);

        $this->reset('file');

        session()->flash('success', __('tickets::messages.attachments.success.uploaded'));
    }

    public function delete(int $attachmentId): void
    {
        $attachment = $this->ticket->attachments()->findOrFail($attachmentId);

        $this->authorize('delete', $attachment);

        app(DeleteTicketAttachment::class)($attachment);

        session()->flash('success', __('tickets::messages.attachments.success.deleted'));
    }

    public function render(): View
    {
        return view('tickets::livewire.ticket-attachments', [
            'attachments' => $this->ticket->attachments()->with('uploader')->latest()->get(),
        ]);
    }
}
