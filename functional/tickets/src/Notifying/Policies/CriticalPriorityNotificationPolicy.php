<?php

namespace Functional\Tickets\Notifying\Policies;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Notifying\Channels\ImmediateAlertTicketChannel;
use Functional\Tickets\Notifying\Channels\TicketMailChannel;
use Functional\Tickets\Notifying\Channels\UrgentTicketChannel;
use Functional\Tickets\Notifying\HandlesTicketPriority;
use Functional\Tickets\Notifying\TicketNotificationPolicy;

class CriticalPriorityNotificationPolicy implements HandlesTicketPriority, TicketNotificationPolicy
{
    public function __construct(
        private readonly TicketMailChannel $mail,
        private readonly UrgentTicketChannel $urgent,
        private readonly ImmediateAlertTicketChannel $alert,
    ) {
    }

    public function handles(): TicketPriority
    {
        return TicketPriority::Critical;
    }

    public function notify(Ticket $ticket): void
    {
        $this->mail->deliver($ticket);
        $this->urgent->deliver($ticket);
        $this->alert->deliver($ticket);
    }
}
