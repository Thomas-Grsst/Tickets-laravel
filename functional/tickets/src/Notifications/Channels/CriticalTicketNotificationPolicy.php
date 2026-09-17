<?php

namespace Functional\Tickets\Notifications\Channels;

class CriticalTicketNotificationPolicy implements TicketNotificationChannelPolicy
{
    /**
     * @return list<string>
     */
    public function channels(): array
    {
        return ['mail', 'urgent', 'immediate-alert'];
    }
}
