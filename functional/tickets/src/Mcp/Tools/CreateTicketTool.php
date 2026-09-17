<?php

namespace Functional\Tickets\Mcp\Tools;

use Functional\Tickets\Actions\CreateTicket;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

/**
 * Creates through the same CreateTicket action the Livewire form uses — the Tool only
 * adapts input and reports the outcome, it never re-decides who is allowed to create.
 */
#[Description('Create a new ticket on behalf of the authenticated user.')]
class CreateTicketTool extends Tool
{
    public function handle(Request $request): Response
    {
        if (! Gate::allows('create', Ticket::class)) {
            return Response::error('You are not allowed to create tickets.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'string', 'in:'.implode(',', array_column(TicketPriority::cases(), 'value'))],
        ]);

        $ticket = app(CreateTicket::class)(auth()->user(), $validated);

        return Response::json(['id' => $ticket->id, 'status' => $ticket->status->value]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('A short summary of the issue.')->required(),
            'description' => $schema->string()->description('The full description of the issue.')->required(),
            'priority' => $schema->string()->enum(array_column(TicketPriority::cases(), 'value'))
                ->description('How urgent the ticket is.')->required(),
        ];
    }
}
