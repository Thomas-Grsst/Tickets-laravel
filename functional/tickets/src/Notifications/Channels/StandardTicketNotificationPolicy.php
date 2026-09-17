<?php

namespace Functional\Tickets\Notifications\Channels;

class StandardTicketNotificationPolicy implements TicketNotificationChannelPolicy
{
    /**
     * @return list<string>
     */
    public function channels(): array
    {
        return ['mail'];
    }
}
