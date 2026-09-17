<?php

namespace Functional\Tickets\Notifications;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Mail\TicketEscalatedMail;
use Functional\Tickets\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class TicketEscalatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public TicketPriority $breachedPriority,
    ) {
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
        return (new TicketEscalatedMail($this->ticket, $this->breachedPriority))
            ->to($notifiable->routeNotificationFor('mail', $this));
    }
}
