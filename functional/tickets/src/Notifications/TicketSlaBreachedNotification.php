<?php

namespace Functional\Tickets\Notifications;

use Functional\Tickets\Mail\TicketSlaBreachedMail;
use Functional\Tickets\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class TicketSlaBreachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket, public bool $wasEscalated) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): Mailable
    {
        return (new TicketSlaBreachedMail($this->ticket, $this->wasEscalated))
            ->to($notifiable->routeNotificationFor('mail', $this));
    }
}
