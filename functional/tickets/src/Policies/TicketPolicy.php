<?php

namespace Functional\Tickets\Policies;

use Functional\Tickets\Access\Controls\TicketControl;
use Lomkit\Access\Policies\ControlledPolicy;

class TicketPolicy extends ControlledPolicy
{
    /** @var class-string<TicketControl> */
    protected string $control = TicketControl::class;
}
