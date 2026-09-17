<?php

namespace Functional\Tickets\Rest\Actions;

use Functional\Tickets\Enums\TicketAbility;
use Functional\Tickets\Models\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Lomkit\Rest\Actions\Action;

/**
 * Every transition endpoint is targeted: the caller names the tickets by id, so omitting
 * them is a 422 instead of a silent transition of every ticket the perimeter exposes.
 *
 * The package authorizes nothing on `operate` beyond the resource's view perimeter, so the
 * ability is checked here, per ticket.
 */
abstract class TicketTransitionAction extends Action
{
    public $targeted = true;

    public int $maxResources = 50;

    /**
     * @param array<string, mixed>     $fields
     * @param Collection<int, Ticket>  $models
     */
    public function handle(array $fields, Collection $models): void
    {
        foreach ($models as $ticket) {
            Gate::authorize($this->ability()->value, $ticket);

            $this->transition($ticket, $fields);
        }
    }

    abstract protected function ability(): TicketAbility;

    /**
     * @param array<string, mixed> $fields
     */
    abstract protected function transition(Ticket $ticket, array $fields): void;
}
