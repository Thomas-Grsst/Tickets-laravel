<?php

namespace Functional\Tickets\Mcp\Tools;

use Functional\Tickets\Enums\TicketAbility;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Mcp\TicketSummary;
use Functional\Tickets\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

/**
 * The requester is the authenticated user, never a field the agent may choose: a ticket
 * opened through this tool lands in the caller's own perimeter, exactly like one opened from
 * the web form.
 *
 * Opening a ticket carries no rule beyond that — the initial status is the model's own
 * default — so the tool creates the row the way the form and the CSV import already do,
 * rather than adding a pass-through action class in front of it.
 */
#[Name('create-ticket')]
#[Description('Opens a new ticket on behalf of the authenticated user.')]
class CreateTicketTool extends Tool
{
    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()
                ->max(255)
                ->required()
                ->description('A one-line summary of the problem.'),
            'description' => $schema->string()
                ->required()
                ->description('Everything a technician needs to reproduce or understand the problem.'),
            'priority' => $schema->string()
                ->enum(TicketPriority::class)
                ->default(TicketPriority::Normal->value)
                ->description('How urgent the ticket is; it decides the SLA the ticket is measured against.'),
        ];
    }

    public function handle(Request $request, TicketSummary $summary): ResponseFactory
    {
        Gate::authorize(TicketAbility::Create->value, Ticket::class);

        $attributes = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => [Rule::enum(TicketPriority::class)],
        ]);

        $ticket = Ticket::query()->create($attributes + [
            'priority' => TicketPriority::Normal->value,
            'requester_id' => $request->user()?->getAuthIdentifier(),
        ]);

        return Response::structured($summary($ticket->load(['requester', 'assignedTechnician'])));
    }
}
