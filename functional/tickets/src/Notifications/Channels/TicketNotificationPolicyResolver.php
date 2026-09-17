<?php

namespace Functional\Tickets\Notifications\Channels;

use Functional\Tickets\Enums\TicketPriority;

class TicketNotificationPolicyResolver
{
    /**
     * The only place a priority is tied to a policy. Adding a new tier is a matter of
     * writing one more policy class and adding it here — nothing else changes.
     *
     * @var array<string, class-string<TicketNotificationChannelPolicy>>
     */
    private array $policies = [
        TicketPriority::Low->value => StandardTicketNotificationPolicy::class,
        TicketPriority::Normal->value => StandardTicketNotificationPolicy::class,
        TicketPriority::High->value => UrgentTicketNotificationPolicy::class,
        TicketPriority::Critical->value => CriticalTicketNotificationPolicy::class,
    ];

    public function resolve(TicketPriority $priority): TicketNotificationChannelPolicy
    {
        return app($this->policies[$priority->value]);
    }
}
