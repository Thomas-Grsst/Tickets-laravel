<?php

namespace Functional\Tickets\Rest\Resources;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Rest\Resources\UserResource;
use Illuminate\Validation\Rule;
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
        ];
    }

    /** @return list<Relation> */
    public function relations(RestRequest $request): array
    {
        return [
            BelongsTo::make('requester', UserResource::class)
                ->requiredOnCreation()
                ->prohibitedOnUpdate(),
            BelongsTo::make('assignedTechnician', UserResource::class),
            HasMany::make('comments', CommentResource::class),
        ];
    }

    /** @return array<string, list<mixed>> */
    public function rules(RestRequest $request): array
    {
        return [
            'id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'title' => ['string', 'max:255'],
            'description' => ['string'],
            'status' => [Rule::enum(TicketStatus::class)],
            'priority' => [Rule::enum(TicketPriority::class)],
            'resolved_at' => ['nullable', 'date'],
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
