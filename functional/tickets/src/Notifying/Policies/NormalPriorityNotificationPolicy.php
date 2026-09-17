<?php

namespace Functional\Tickets\Notifying\Policies;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Notifying\Channels\TicketMailChannel;
use Functional\Tickets\Notifying\HandlesTicketPriority;
use Functional\Tickets\Notifying\TicketNotificationPolicy;

class NormalPriorityNotificationPolicy implements HandlesTicketPriority, TicketNotificationPolicy
{
    public function __construct(private readonly TicketMailChannel $mail)
    {
    }

    public function handles(): TicketPriority
    {
        return TicketPriority::Normal;
    }

    public function notify(Ticket $ticket): void
    {
        $this->mail->deliver($ticket);
    }
}
