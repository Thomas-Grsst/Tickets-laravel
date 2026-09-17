<?php

namespace Functional\Tickets\Rest\Resources;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Rest\Actions\AssignTicketAction;
use Functional\Tickets\Rest\Actions\CloseTicketAction;
use Functional\Tickets\Rest\Actions\ReopenTicketAction;
use Functional\Tickets\Rest\Actions\ResolveTicketAction;
use Functional\Tickets\Rest\Actions\StartTicketProgressAction;
use Functional\Tickets\Rest\Actions\UnassignTicketAction;
use Functional\Users\Rest\Resources\UserResource;
use Illuminate\Validation\Rule;
use Lomkit\Rest\Actions\Action;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Relations\BelongsTo;
use Lomkit\Rest\Relations\HasMany;
use Lomkit\Rest\Relations\Relation;
use Technical\Framework\Rest\Resources\Resource;

class TicketResource extends Resource
{
    /** @var class-string<Ticket> */
    public static $model = Ticket::class;

    /** @return list<string> */
    public function fields(RestRequest $request): array
    {
        return [
            'id',
            'title',
            'description',
            'status',
            'priority',
            'created_at',
            'resolved_at',
            'sla_met',
        ];
    }

    /**
     * The transitions are the only way a ticket changes hands or state, so the assignment
     * relation is readable but never mutable through the CRUD endpoint.
     *
     * @return list<Relation>
     */
    public function relations(RestRequest $request): array
    {
        return [
            BelongsTo::make('requester', UserResource::class)
                ->requiredOnCreation()
                ->prohibitedOnUpdate(),
            BelongsTo::make('assignedTechnician', UserResource::class)
                ->prohibitedOnCreation()
                ->prohibitedOnUpdate(),
            HasMany::make('comments', CommentResource::class),
            HasMany::make('attachments', AttachmentResource::class),
        ];
    }

    /**
     * `status`, `resolved_at` and `sla_met` are lifecycle outputs: letting a client write
     * them directly would route around the transition table the actions enforce.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(RestRequest $request): array
    {
        return [
            'id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'status' => ['prohibited'],
            'resolved_at' => ['prohibited'],
            'sla_met' => ['prohibited'],
            'title' => ['string', 'max:255'],
            'description' => ['string'],
            'priority' => [Rule::enum(TicketPriority::class)],
        ];
    }

    /**
     * Every lifecycle move is its own endpoint under /tickets/actions/{uriKey}, each one
     * targeted so a caller must name the tickets it is moving.
     *
     * @return list<Action>
     */
    public function actions(RestRequest $request): array
    {
        return [
            AssignTicketAction::make(),
            UnassignTicketAction::make(),
            StartTicketProgressAction::make(),
            ResolveTicketAction::make(),
            ReopenTicketAction::make(),
            CloseTicketAction::make(),
        ];
    }

    /** @return array<string, list<mixed>> */
    public function createRules(RestRequest $request): array
    {
        return [
            'title' => ['required'],
            'description' => ['required'],
            'priority' => ['required'],
        ];
    }

    /** @return array<string, string> */
    public function defaultOrderBy(RestRequest $request): array
    {
        return ['created_at' => 'desc'];
    }
}
