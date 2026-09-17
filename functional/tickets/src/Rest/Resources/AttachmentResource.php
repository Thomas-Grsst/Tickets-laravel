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
            'original_name',
            'mime_type',
            'size',
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
     * Attachments are only ever written through the upload endpoint, which handles the
     * file itself — the REST mutate path stays read-only for this resource.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(RestRequest $request): array
    {
        return [
            'id' => ['prohibited'],
            'original_name' => ['prohibited'],
            'mime_type' => ['prohibited'],
            'size' => ['prohibited'],
            'created_at' => ['prohibited'],
        ];
    }
}
