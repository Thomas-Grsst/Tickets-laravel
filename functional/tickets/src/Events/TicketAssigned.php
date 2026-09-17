<?php

namespace Functional\Tickets\Events;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketAssigned implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public User $technician,
    ) {}

    /**
     * Broadcasts once per user allowed to see this ticket — the requester, the assigned
     * technician, and every manager — instead of on a shared per-ticket channel, so the
     * client never has to authorize per row: a user simply never receives what they
     * cannot see.
     *
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        $recipientIds = collect([$this->ticket->requester_id, $this->technician->id])
            ->merge(User::permission(TicketPermission::ViewAllTickets->value)->pluck('id'))
            ->unique();

        return $recipientIds
            ->map(fn (int $userId): Channel => new PrivateChannel("users.{$userId}.tickets"))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'ticket.assigned';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['ticket_id' => $this->ticket->getKey()];
    }
}
