<?php

namespace Functional\Tickets\Events;

use Functional\Tickets\Broadcasting\TicketChannel;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A ticket moved from one lifecycle state to another. Fired by the single gate every
 * transition goes through, so the six transition actions all announce themselves without
 * each one remembering to.
 */
class TicketStatusChanged implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public TicketStatus $previousStatus,
        public TicketStatus $currentStatus,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return TicketChannel::for($this->ticket);
    }

    public function broadcastAs(): string
    {
        return 'ticket.status-changed';
    }

    /**
     * The payload is an allow-list, not the ticket: the client only needs to know which
     * row went stale, and re-reads it through its own access-controlled query.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->ticket->getKey(),
            'status' => $this->currentStatus->value,
        ];
    }
}
