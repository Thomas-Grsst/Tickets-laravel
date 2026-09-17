<?php

namespace Functional\Tickets\Notifications;

use Functional\Tickets\Mail\TicketAssignedMail;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Notifications\Channels\TicketNotificationPolicyResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    /**
     * Delegates the channel choice to the priority's policy — never a match/if here, so a
     * new priority tier only ever costs a new policy class plus one resolver entry.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return app(TicketNotificationPolicyResolver::class)->resolve($this->ticket->priority)->channels();
    }

    public function toMail(object $notifiable): Mailable
    {
        return (new TicketAssignedMail($this->ticket))
            ->to($notifiable->routeNotificationFor('mail', $this));
    }
}
