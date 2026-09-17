<?php

namespace Functional\Tickets\Notifying\Channels;

use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Functional\Tickets\Notifying\TicketManagers;
use Illuminate\Support\Facades\Log;

/**
 * Stands in for paging the managers on the spot. Simulated like the urgent channel, but
 * kept separate because it targets a named audience rather than a transport.
 */
class ImmediateAlertTicketChannel implements TicketNotificationChannel
{
    public const NAME = 'immediate-alert';

    public function __construct(private readonly TicketManagers $managers)
    {
    }

    public function deliver(Ticket $ticket): void
    {
        $recipients = ($this->managers)()
            ->map(static fn (User $manager): int => $manager->getKey())
            ->all();

        Log::critical(
            __('tickets::notifications.immediate_alert.line', [
                'title' => $ticket->title,
                'priority' => $ticket->priority->value,
                'recipients' => count($recipients),
            ]),
            [
                'channel' => self::NAME,
                'ticket_id' => $ticket->getKey(),
                'priority' => $ticket->priority->value,
                'recipient_ids' => $recipients,
            ],
        );
    }
}
