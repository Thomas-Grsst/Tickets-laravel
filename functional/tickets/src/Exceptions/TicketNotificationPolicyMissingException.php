<?php

namespace Functional\Tickets\Exceptions;

use Functional\Tickets\Enums\TicketPriority;
use LogicException;

/**
 * A priority with no tier behind it means the container tag was not updated when the case
 * was added — a wiring mistake, never something a request can provoke.
 */
class TicketNotificationPolicyMissingException extends LogicException
{
    public function __construct(TicketPriority $priority)
    {
        parent::__construct(sprintf(
            'No ticket notification policy is registered for priority %s.',
            $priority->value,
        ));
    }
}
