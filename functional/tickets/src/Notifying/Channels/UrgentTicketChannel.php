<?php

namespace Functional\Tickets\Notifying\Channels;

use Functional\Tickets\Models\Ticket;
use Illuminate\Support\Facades\Log;

/**
 * Stands in for the out-of-band urgent transport (SMS, Slack, pager). Only the emission is
 * simulated — the tier that reaches for it, and when, is the real rule.
 */
class UrgentTicketChannel implements TicketNotificationChannel
{
    public const NAME = 'urgent';

    public function deliver(Ticket $ticket): void
    {
        Log::warning(
            __('tickets::notifications.urgent_channel.line', [
                'title' => $ticket->title,
                'priority' => $ticket->priority->value,
                'hours' => $ticket->priority->slaHours(),
            ]),
            [
                'channel' => self::NAME,
                'ticket_id' => $ticket->getKey(),
                'priority' => $ticket->priority->value,
            ],
        );
    }
}
