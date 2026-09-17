<?php

namespace Functional\Users\Rest\Resources;

use Functional\Users\Models\User;
use Lomkit\Rest\Http\Requests\RestRequest;
use Technical\Framework\Rest\Resources\Resource;

class UserResource extends Resource
{
    /** @var class-string<User> */
    public static $model = User::class;

    /** @return list<string> */
    public function fields(RestRequest $request): array
    {
        return [
            'id',
            'name',
        ];
    }

    /** @return array<string, list<mixed>> */
    public function rules(RestRequest $request): array
    {
        return [
            'id' => ['prohibited'],
            'name' => ['prohibited'],
        ];
    }
}
