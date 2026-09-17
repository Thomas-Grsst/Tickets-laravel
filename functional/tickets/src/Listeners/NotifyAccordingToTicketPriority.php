<?php

namespace Functional\Tickets\Listeners;

use Functional\Tickets\Events\TicketAssigned;
use Functional\Tickets\Notifying\TicketNotificationPolicies;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Sits beside NotifyTechnicianOfTicketAssignment rather than inside it: that listener owes
 * the technician their assignment mail whatever the priority, while this one decides how
 * loudly the handling side hears about it. Two audiences, two reasons to change.
 */
class NotifyAccordingToTicketPriority implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public function __construct(private readonly TicketNotificationPolicies $policies)
    {
    }

    public function handle(TicketAssigned $event): void
    {
        $this->policies->for($event->ticket->priority)->notify($event->ticket);
    }
}
