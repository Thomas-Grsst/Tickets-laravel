<?php

namespace Functional\Tickets\Notifications\Channels;

class UrgentTicketNotificationPolicy implements TicketNotificationChannelPolicy
{
    /**
     * @return list<string>
     */
    public function channels(): array
    {
        return ['mail', 'urgent'];
    }
}
