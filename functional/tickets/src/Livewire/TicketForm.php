<?php

namespace Functional\Tickets\Livewire;

use Functional\Tickets\Actions\AssignTicket;
use Functional\Tickets\Actions\CloseTicket;
use Functional\Tickets\Actions\ReopenTicket;
use Functional\Tickets\Actions\ResolveTicket;
use Functional\Tickets\Actions\StartTicketProgress;
use Functional\Tickets\Actions\UnassignTicket;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Exceptions\IllegalTicketTransitionException;
use Functional\Tickets\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class TicketForm extends Component
{
    public ?Ticket $ticket = null;

    public string $title = '';

    public string $description = '';

    public string $priority = '';

    public bool $isEditable = false;

    public function mount(?Ticket $ticket = null): void
    {
        if ($ticket) {
            $this->authorize('view', $ticket);

            $this->ticket = $ticket;
            $this->title = $ticket->title;
            $this->description = $ticket->description;
            $this->priority = $ticket->priority->value;
            $this->isEditable = Gate::allows('update', $ticket);

            return;
        }

        $this->authorize('create', Ticket::class);
        $this->priority = TicketPriority::Normal->value;
        $this->isEditable = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
        ]);

        if ($this->ticket) {
            $this->authorize('update', $this->ticket);
            $this->ticket->update($validated);

            session()->flash('success', __('tickets::messages.form.success.updated'));

            return;
        }

        $this->ticket = Ticket::create($validated + ['requester_id' => auth()->id()]);

        session()->flash('success', __('tickets::messages.form.success.created'));

        $this->redirectRoute('tickets.edit', $this->ticket, navigate: true);
    }

    public function assign(): void
    {
        $this->authorize('assign', $this->ticket);
        $this->applyTransition(fn () => (new AssignTicket)($this->ticket, auth()->user()));
    }

    public function unassign(): void
    {
        $this->authorize('assign', $this->ticket);
        $this->applyTransition(fn () => (new UnassignTicket)($this->ticket));
    }

    public function startProgress(): void
    {
        $this->authorize('update', $this->ticket);
        $this->applyTransition(fn () => (new StartTicketProgress)($this->ticket));
    }

    public function resolve(): void
    {
        $this->authorize('update', $this->ticket);
        $this->applyTransition(fn () => (new ResolveTicket)($this->ticket));
    }

    public function reopen(): void
    {
        $this->authorize('update', $this->ticket);
        $this->applyTransition(fn () => (new ReopenTicket)($this->ticket));
    }

    public function close(): void
    {
        $this->authorize('close', $this->ticket);
        $this->applyTransition(fn () => (new CloseTicket)($this->ticket));
    }

    private function applyTransition(callable $action): void
    {
        // @phpstan-ignore xefi.noTryCatch (converts the one expected transition conflict into a user-facing flash message, per TP6)
        try {
            $action();
        } catch (IllegalTicketTransitionException) {
            session()->flash('error', __('tickets::messages.form.error.transition'));

            return;
        }

        $this->ticket->refresh();

        session()->flash('success', __('tickets::messages.form.success.transitioned', [
            'status' => __('tickets::messages.status.'.$this->ticket->status->value),
        ]));
    }

    /**
     * @return list<array{method: string, label: string}>
     */
    private function availableActions(): array
    {
        if (! $this->ticket) {
            return [];
        }

        $canTo = fn (TicketStatus $target) => $this->ticket->status->canTransitionTo($target);
        $actions = [];

        if ($canTo(TicketStatus::Assigned)) {
            $actions[] = ['method' => 'assign', 'label' => __('tickets::messages.form.actions.assign')];
        }

        if ($this->ticket->status === TicketStatus::Assigned && $canTo(TicketStatus::Open)) {
            $actions[] = ['method' => 'unassign', 'label' => __('tickets::messages.form.actions.unassign')];
        }

        if ($this->ticket->status === TicketStatus::Assigned && $canTo(TicketStatus::InProgress)) {
            $actions[] = ['method' => 'startProgress', 'label' => __('tickets::messages.form.actions.start_progress')];
        }

        if ($canTo(TicketStatus::Resolved)) {
            $actions[] = ['method' => 'resolve', 'label' => __('tickets::messages.form.actions.resolve')];
        }

        if ($this->ticket->status === TicketStatus::Resolved && $canTo(TicketStatus::InProgress)) {
            $actions[] = ['method' => 'reopen', 'label' => __('tickets::messages.form.actions.reopen')];
        }

        if ($canTo(TicketStatus::Closed)) {
            $actions[] = ['method' => 'close', 'label' => __('tickets::messages.form.actions.close')];
        }

        return $actions;
    }

    public function render(): View
    {
        return view('tickets::livewire.ticket-form', [
            'priorities' => TicketPriority::cases(),
            'availableActions' => $this->availableActions(),
        ])->layout('layouts.app', [
            'title' => $this->ticket ? __('tickets::messages.form.edit_title') : __('tickets::messages.form.create_title'),
        ]);
    }
}
