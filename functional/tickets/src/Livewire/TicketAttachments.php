<?php

namespace Functional\Tickets\Livewire;

use Functional\Tickets\Actions\StoreTicketAttachment;
use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Validation\AttachmentFileRules;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class TicketAttachments extends Component
{
    use WithFileUploads;

    public Ticket $ticket;

    public ?TemporaryUploadedFile $file = null;

    public bool $isUploadable = false;

    public function mount(Ticket $ticket): void
    {
        $this->authorize('view', $ticket);

        $this->ticket = $ticket;
        $this->isUploadable = Gate::allows('create', [Attachment::class, $ticket]);
    }

    public function upload(StoreTicketAttachment $storeTicketAttachment): void
    {
        $this->authorize('create', [Attachment::class, $this->ticket]);

        $this->validate(['file' => AttachmentFileRules::forUploadedFile()]);

        $storeTicketAttachment($this->ticket, $this->file, auth()->user());

        $this->reset('file');

        session()->flash('attachment_success', __('tickets::messages.attachments.success.uploaded'));
    }

    public function delete(int $attachmentId): void
    {
        $attachment = $this->ticket->attachments()->findOrFail($attachmentId);

        $this->authorize('delete', $attachment);

        $attachment->delete();

        session()->flash('attachment_success', __('tickets::messages.attachments.success.deleted'));
    }

    /**
     * @return Collection<int, Attachment>
     */
    private function attachments(): Collection
    {
        return $this->ticket->attachments()->with('uploader')->latest()->get();
    }

    public function render(): View
    {
        return view('tickets::livewire.ticket-attachments', [
            'attachments' => $this->attachments(),
        ]);
    }
}
