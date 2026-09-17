<?php

namespace Functional\Tickets\Jobs;

use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Exceptions\TicketSlaNotMeasurableException;
use Functional\Tickets\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordTicketSlaCompliance implements ShouldQueue
{
    use Queueable;

    public function __construct(private Ticket $ticket)
    {
    }

    /**
     * SerializesModels re-fetches the ticket, so the precondition below is what makes a
     * pre-commit dispatch observable instead of silently recording a wrong verdict.
     *
     * @throws TicketSlaNotMeasurableException
     */
    public function handle(): void
    {
        if ($this->ticket->status !== TicketStatus::Resolved || $this->ticket->resolved_at === null) {
            throw new TicketSlaNotMeasurableException($this->ticket);
        }

        $elapsedHours = $this->ticket->created_at->diffInHours($this->ticket->resolved_at);

        $this->ticket
            ->forceFill(['sla_met' => $elapsedHours <= $this->ticket->priority->slaHours()])
            ->save();
    }
}
