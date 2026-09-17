<?php

namespace Functional\Tickets\Rest\Resources;

use Functional\Tickets\Models\Attachment;
use Functional\Users\Rest\Resources\UserResource;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Relations\BelongsTo;
use Lomkit\Rest\Relations\Relation;
use Technical\Framework\Rest\Resources\Resource;

class AttachmentResource extends Resource
{
    /** @var class-string<Attachment> */
    public static $model = Attachment::class;

    /** @return list<string> */
    public function fields(RestRequest $request): array
    {
        return [
            'id',
            'name',
            'kind',
            'mime_type',
            'size_in_bytes',
            'created_at',
        ];
    }

    /** @return list<Relation> */
    public function relations(RestRequest $request): array
    {
        return [
            BelongsTo::make('uploader', UserResource::class),
        ];
    }

    /**
     * An attachment only ever exists because bytes were stored first, so the whole row is
     * read-only here — the upload endpoint is the single way in.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(RestRequest $request): array
    {
        return [
            'id' => ['prohibited'],
            'name' => ['prohibited'],
            'kind' => ['prohibited'],
            'mime_type' => ['prohibited'],
            'size_in_bytes' => ['prohibited'],
            'created_at' => ['prohibited'],
        ];
    }
}
