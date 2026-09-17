<?php

namespace Functional\Tickets\Mcp\Tools;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

/**
 * Delegates entirely to the same `controlled()` scope the API and the Livewire list use —
 * an agent authenticated as a requester sees exactly the tickets that user would see
 * anywhere else in the app, because the perimeter is applied here too, not re-decided.
 */
#[Description('Search tickets visible to the authenticated user, optionally filtered by status or priority.')]
class SearchTicketsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
        ]);

        $tickets = Ticket::query()
            ->controlled()
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'title', 'status', 'priority', 'created_at']);

        return Response::json($tickets);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->enum(array_column(TicketStatus::cases(), 'value'))
                ->description('Filter by ticket status.'),
            'priority' => $schema->string()->enum(array_column(TicketPriority::cases(), 'value'))
                ->description('Filter by ticket priority.'),
        ];
    }
}
