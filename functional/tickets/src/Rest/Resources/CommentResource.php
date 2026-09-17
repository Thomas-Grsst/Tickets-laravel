<?php

namespace Functional\Tickets\Rest\Resources;

use Functional\Tickets\Models\Comment;
use Functional\Users\Rest\Resources\UserResource;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Relations\BelongsTo;
use Lomkit\Rest\Relations\Relation;
use Technical\Framework\Rest\Resources\Resource;

class CommentResource extends Resource
{
    /** @var class-string<Comment> */
    public static $model = Comment::class;

    /** @return list<string> */
    public function fields(RestRequest $request): array
    {
        return [
            'id',
            'body',
            'created_at',
        ];
    }

    /** @return list<Relation> */
    public function relations(RestRequest $request): array
    {
        return [
            BelongsTo::make('author', UserResource::class),
        ];
    }

    /** @return array<string, list<mixed>> */
    public function rules(RestRequest $request): array
    {
        return [
            'id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'body' => ['string', 'max:2000'],
        ];
    }

    /** @return array<string, list<mixed>> */
    public function createRules(RestRequest $request): array
    {
        return [
            'body' => ['required'],
        ];
    }
}
