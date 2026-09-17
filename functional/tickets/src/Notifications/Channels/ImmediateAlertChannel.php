<?php

namespace Functional\Tickets\Notifications\Channels;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Users\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * The extra escalation path for Critical tickets: every manager gets an immediate, loud
 * signal alongside whatever channel the notifiable itself was reached on. A real deployment
 * would push this to an on-call system; a structured log is enough for this exercise.
 */
class ImmediateAlertChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $managerIds = User::permission(TicketPermission::ViewAllTickets->value)->pluck('id');

        Log::critical('tickets.notifications.immediate_alert', [
            'notifiable_id' => $notifiable->getKey(),
            'notification' => $notification::class,
            'alerted_manager_ids' => $managerIds->all(),
        ]);
    }
}
