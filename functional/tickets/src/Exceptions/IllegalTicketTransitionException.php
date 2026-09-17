<?php

namespace Functional\Tickets\Exceptions;

use Functional\Tickets\Enums\TicketStatus;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * A transition absent from the lifecycle table always means the same thing to a client —
 * the request conflicts with the ticket's current state — so the exception carries the 409
 * itself instead of being mapped to one at the boundary.
 */
class IllegalTicketTransitionException extends ConflictHttpException
{
    public function __construct(TicketStatus $from, TicketStatus $target)
    {
        parent::__construct(__('tickets::messages.transitions.illegal', [
            'from' => $from->value,
            'target' => $target->value,
        ]));
    }
}
