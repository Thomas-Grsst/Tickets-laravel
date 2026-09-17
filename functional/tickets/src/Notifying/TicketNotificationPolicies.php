<?php

namespace Functional\Tickets\Notifying;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Exceptions\TicketNotificationPolicyMissingException;

/**
 * The only dispatch point of the notification policy. Tiers are tagged in the container and
 * index themselves here, so a new priority costs a class plus its tag entry and no edit to
 * anything that already resolves a policy.
 */
class TicketNotificationPolicies
{
    public const TAG = 'tickets.notification-policies';

    /** @var array<string, TicketNotificationPolicy> */
    private array $byPriority = [];

    /**
     * @param iterable<TicketNotificationPolicy&HandlesTicketPriority> $policies
     */
    public function __construct(iterable $policies)
    {
        foreach ($policies as $policy) {
            $this->byPriority[$policy->handles()->value] = $policy;
        }
    }

    public function for(TicketPriority $priority): TicketNotificationPolicy
    {
        return $this->byPriority[$priority->value]
            ?? throw new TicketNotificationPolicyMissingException($priority);
    }
}
