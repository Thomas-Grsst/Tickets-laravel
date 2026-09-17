<?php

namespace Functional\Tickets\Rest\Actions;

use Functional\Tickets\Actions\AssignTicket;
use Functional\Tickets\Enums\TicketAbility;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Validation\Rule;
use Lomkit\Rest\Http\Requests\RestRequest;

class AssignTicketAction extends TicketTransitionAction
{
    /**
     * @return array<string, list<mixed>>
     */
    public function fields(RestRequest $request): array
    {
        return [
            'technician_id' => ['required', 'integer', Rule::exists(User::class, 'id')],
        ];
    }

    protected function ability(): TicketAbility
    {
        return TicketAbility::Assign;
    }

    /**
     * @param array<string, mixed> $fields
     */
    protected function transition(Ticket $ticket, array $fields): void
    {
        app(AssignTicket::class)($ticket, User::findOrFail($fields['technician_id']));
    }
}
