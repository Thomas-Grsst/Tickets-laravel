<?php

namespace Functional\Tickets\Notifications\Channels;

interface TicketNotificationChannelPolicy
{
    /**
     * @return list<string>
     */
    public function channels(): array;
}
