<?php

namespace Functional\Tickets\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Simulates a real urgency dispatch (SMS, pager, chat webhook, ...) with a structured log
 * line — wiring an actual provider is not the point of this extension point.
 */
class UrgentChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        Log::warning('tickets.notifications.urgent', [
            'notifiable_id' => $notifiable->getKey(),
            'notification' => $notification::class,
        ]);
    }
}
