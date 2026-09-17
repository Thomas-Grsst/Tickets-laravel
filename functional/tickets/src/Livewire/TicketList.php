<?php

namespace Functional\Tickets\Livewire;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TicketList extends Component
{
    use WithPagination;

    /** @var list<string> */
    private const SORTABLE = ['title', 'status', 'priority', 'created_at'];

    /**
     * The tickets the browser holds an open private channel for — exactly the rows the
     * access-controlled query just returned. Locked, so the page can only ever ask for the
     * channels the server itself listed, and each of those is still authorised on
     * subscription.
     *
     * @var list<int>
     */
    #[Locked]
    public array $subscribedTicketIds = [];

    #[Url]
    public string $status = '';

    #[Url]
    public string $priority = '';

    #[Url]
    public string $sort = 'created_at';

    #[Url]
    public string $direction = 'desc';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPriority(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
            return;
        }

        $this->direction = $this->sort === $column && $this->direction === 'asc' ? 'desc' : 'asc';
        $this->sort = $column;
    }

    public function render(): View
    {
        $sort = in_array($this->sort, self::SORTABLE, true) ? $this->sort : 'created_at';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        $tickets = Ticket::query()
            ->controlled()
            ->with(['requester', 'assignedTechnician'])
            ->withCount('comments')
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->priority !== '', fn ($query) => $query->where('priority', $this->priority))
            ->orderBy($sort, $direction)
            ->paginate(25);

        $this->subscribedTicketIds = $tickets->getCollection()
            ->map(static fn (Ticket $ticket): int => (int) $ticket->getKey())
            ->values()
            ->all();

        return view('tickets::livewire.ticket-list', [
            'tickets' => $tickets,
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
            'sortColumn' => $sort,
            'sortDirection' => $direction,
        ])->layout('layouts.app', ['title' => __('tickets::messages.nav.tickets')]);
    }
}
