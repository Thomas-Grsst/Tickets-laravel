<?php

namespace Functional\Tickets\Notifications;

use Functional\Tickets\Mail\TicketAssignedMail;
use Functional\Tickets\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket)
    {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): Mailable
    {
        return (new TicketAssignedMail($this->ticket))
            ->to($notifiable->routeNotificationFor('mail', $this));
    }
}
