<?php

namespace Functional\Tickets\Mcp\Tools;

use Functional\Tickets\Enums\TicketAbility;
use Functional\Tickets\Mcp\TicketSummary;
use Functional\Tickets\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * A ticket outside the caller's perimeter is reported as invisible rather than forbidden:
 * the lookup runs through `controlled()`, so the tool never learns the row exists either.
 */
#[Name('view-ticket')]
#[IsReadOnly]
#[Description('Reads one ticket in full, by id. The ticket must be inside the authenticated user\'s perimeter.')]
class ViewTicketTool extends Tool
{
    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()
                ->min(1)
                ->required()
                ->description('The identifier of the ticket to read, as returned by the search tool.'),
        ];
    }

    public function handle(Request $request, TicketSummary $summary): Response|ResponseFactory
    {
        Gate::authorize(TicketAbility::ViewAny->value, Ticket::class);

        $criteria = $request->validate([
            'ticket_id' => ['required', 'integer'],
        ]);

        $ticket = Ticket::query()
            ->controlled()
            ->with(['requester', 'assignedTechnician'])
            ->find($criteria['ticket_id']);

        if (! $ticket instanceof Ticket) {
            return Response::error((string) __('tickets::messages.mcp.error.ticket_not_visible', [
                'id' => $criteria['ticket_id'],
            ]));
        }

        return Response::structured($summary($ticket));
    }
}
