<?php

namespace Functional\Tickets\Listeners;

use Functional\Tickets\Events\TicketAssigned;
use Functional\Tickets\Notifications\TicketAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyTechnicianOfTicketAssignment implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The listener re-reads the ticket on the worker, so it must not be queued before the
     * assignment transaction commits. Name is fixed by Laravel's queue interaction contract.
     *
     * @phpstan-ignore xefi.booleanPropertyNaming
     */
    public bool $afterCommit = true;

    public function handle(TicketAssigned $event): void
    {
        $event->technician->notify(new TicketAssignedNotification($event->ticket));
    }
}
