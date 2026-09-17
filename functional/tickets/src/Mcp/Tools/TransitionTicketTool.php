<?php

namespace Functional\Tickets\Mcp\Tools;

use Functional\Tickets\Exceptions\IllegalTicketTransitionException;
use Functional\Tickets\Mcp\TicketSummary;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Rest\Actions\AssignTicketAction;
use Functional\Tickets\Rest\Actions\CloseTicketAction;
use Functional\Tickets\Rest\Actions\ReopenTicketAction;
use Functional\Tickets\Rest\Actions\ResolveTicketAction;
use Functional\Tickets\Rest\Actions\StartTicketProgressAction;
use Functional\Tickets\Rest\Actions\TicketTransitionAction;
use Functional\Tickets\Rest\Actions\UnassignTicketAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Lomkit\Rest\Http\Requests\RestRequest;
use Throwable;

/**
 * The agent is given the very transitions the REST API exposes under
 * `/api/v1/tickets/actions/{transition}` — the same classes, named by the same uri keys, so
 * the tool owns neither the ability check nor the lifecycle table. A move absent from that
 * table still comes back as the domain's own refusal rather than a generic failure, because
 * `laravel/mcp` masks unrecognised exceptions outside debug mode.
 */
#[Name('transition-ticket')]
#[IsDestructive]
#[Description('Moves one ticket through its lifecycle by running one of the transitions the REST API exposes, under the abilities the authenticated user holds.')]
class TransitionTicketTool extends Tool
{
    /** @var list<class-string<TicketTransitionAction>> */
    private const TRANSITIONS = [
        AssignTicketAction::class,
        UnassignTicketAction::class,
        StartTicketProgressAction::class,
        ResolveTicketAction::class,
        ReopenTicketAction::class,
        CloseTicketAction::class,
    ];

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()
                ->min(1)
                ->required()
                ->description('The identifier of the ticket to move.'),
            'transition' => $schema->string()
                ->enum($this->transitionKeys())
                ->required()
                ->description('The lifecycle move to run. Read the tickets://rules resource for which move is legal from which status.'),
            'technician_id' => $schema->integer()
                ->min(1)
                ->description('The user the ticket is handed to. Required by the assign-ticket transition, ignored by the others.'),
        ];
    }

    public function handle(Request $request, TicketSummary $summary): Response|ResponseFactory
    {
        $criteria = $request->validate([
            'ticket_id' => ['required', 'integer'],
            'transition' => ['required', Rule::in($this->transitionKeys())],
        ]);

        $ticket = Ticket::query()->controlled()->find($criteria['ticket_id']);

        if (! $ticket instanceof Ticket) {
            return Response::error((string) __('tickets::messages.mcp.error.ticket_not_visible', [
                'id' => $criteria['ticket_id'],
            ]));
        }

        $transition = $this->transition($criteria['transition']);
        $refusal = $this->run($transition, $request->validate($transition->fields(new RestRequest())), $ticket);

        if ($refusal instanceof IllegalTicketTransitionException) {
            return Response::error($refusal->getMessage());
        }

        return Response::structured($summary($ticket->refresh()->load(['requester', 'assignedTechnician'])));
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function run(TicketTransitionAction $transition, array $fields, Ticket $ticket): ?IllegalTicketTransitionException
    {
        return rescue(
            function () use ($transition, $fields, $ticket): ?IllegalTicketTransitionException {
                $transition->handle($fields, collect([$ticket]));

                return null;
            },
            fn (Throwable $exception): IllegalTicketTransitionException => $exception instanceof IllegalTicketTransitionException
                ? $exception
                : throw $exception,
            report: false,
        );
    }

    /**
     * @return array<string, class-string<TicketTransitionAction>>
     */
    private function transitions(): array
    {
        $transitions = [];

        foreach (self::TRANSITIONS as $actionClass) {
            $transitions[(new $actionClass())->uriKey()] = $actionClass;
        }

        return $transitions;
    }

    /**
     * @return list<string>
     */
    private function transitionKeys(): array
    {
        return array_keys($this->transitions());
    }

    private function transition(string $uriKey): TicketTransitionAction
    {
        $actionClass = $this->transitions()[$uriKey];

        return new $actionClass();
    }
}
