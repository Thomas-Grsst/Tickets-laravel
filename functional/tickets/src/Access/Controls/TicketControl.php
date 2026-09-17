<?php

namespace Functional\Tickets\Access\Controls;

use Functional\Tickets\Access\Perimeters\AllTicketsPerimeter;
use Functional\Tickets\Access\Perimeters\AssignedTicketsPerimeter;
use Functional\Tickets\Access\Perimeters\OwnTicketsPerimeter;
use Functional\Tickets\Models\Ticket;
use Lomkit\Access\Controls\Control;
use Lomkit\Access\Perimeters\Perimeter;

class TicketControl extends Control
{
    /** @var class-string<Ticket> */
    protected string $model = Ticket::class;

    /**
     * The widest perimeter comes first: it does not overlay, so a match on it
     * short-circuits the narrower ones instead of ORing an empty constraint.
     *
     * @return list<Perimeter>
     */
    protected function perimeters(): array
    {
        return [
            AllTicketsPerimeter::new(),
            AssignedTicketsPerimeter::new(),
            OwnTicketsPerimeter::new(),
        ];
    }
}
