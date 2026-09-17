<?php

namespace Functional\Tickets\Mcp\Tools;

use Functional\Tickets\Enums\TicketAbility;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Mcp\TicketSummary;
use Functional\Tickets\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * The query goes through `controlled()`, the same access-control macro the web list and the
 * REST search use, so an agent sees exactly the tickets its user sees — no second definition
 * of who may read what, and no HTTP hop back into our own API.
 */
#[Name('search-tickets')]
#[IsReadOnly]
#[Description('Searches the tickets the authenticated user is allowed to see, optionally narrowed by status, by priority, or by a term matched against the title and the description.')]
class SearchTicketsTool extends Tool
{
    private const MAX_RESULTS = 50;

    private const DEFAULT_RESULTS = 15;

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum(TicketStatus::class)
                ->description('Keep only the tickets currently in this lifecycle state.'),
            'priority' => $schema->string()
                ->enum(TicketPriority::class)
                ->description('Keep only the tickets carrying this priority.'),
            'term' => $schema->string()
                ->max(255)
                ->description('Free text matched against the ticket title and description.'),
            'limit' => $schema->integer()
                ->min(1)
                ->max(self::MAX_RESULTS)
                ->default(self::DEFAULT_RESULTS)
                ->description('How many tickets to return, newest first.'),
        ];
    }

    public function handle(Request $request, TicketSummary $summary): ResponseFactory
    {
        Gate::authorize(TicketAbility::ViewAny->value, Ticket::class);

        $criteria = $request->validate([
            'status' => [Rule::enum(TicketStatus::class)],
            'priority' => [Rule::enum(TicketPriority::class)],
            'term' => ['string', 'max:255'],
            'limit' => ['integer', 'min:1', 'max:' . self::MAX_RESULTS],
        ]);

        $tickets = Ticket::query()
            ->controlled()
            ->with(['requester', 'assignedTechnician'])
            ->when(isset($criteria['status']), fn (Builder $query): Builder => $query->where('status', $criteria['status']))
            ->when(isset($criteria['priority']), fn (Builder $query): Builder => $query->where('priority', $criteria['priority']))
            ->when(isset($criteria['term']), fn (Builder $query): Builder => $this->matching($query, $criteria['term']))
            ->orderByDesc('created_at')
            ->limit($criteria['limit'] ?? self::DEFAULT_RESULTS)
            ->get();

        return Response::structured([
            'count' => $tickets->count(),
            'tickets' => $tickets->map(fn (Ticket $ticket): array => $summary($ticket))->all(),
        ]);
    }

    private function matching(Builder $query, string $term): Builder
    {
        return $query->where(fn (Builder $nested): Builder => $nested
            ->where('title', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%"));
    }
}
