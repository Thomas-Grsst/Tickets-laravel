<?php

namespace Functional\Tickets\Mcp\Tools;

use Functional\Tickets\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('View a single ticket by id, if the authenticated user is allowed to see it.')]
class ViewTicketTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate(['ticket_id' => ['required', 'integer']]);

        $ticket = Ticket::query()->controlled()->find($validated['ticket_id']);

        if ($ticket === null) {
            return Response::error("Ticket #{$validated['ticket_id']} does not exist, or you do not have permission to view it.");
        }

        return Response::json($ticket->only([
            'id', 'title', 'description', 'status', 'priority', 'created_at', 'resolved_at',
        ]));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->description('The ticket id to view.')->required(),
        ];
    }
}
