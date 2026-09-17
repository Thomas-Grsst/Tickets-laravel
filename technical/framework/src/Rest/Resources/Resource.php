<?php

namespace Technical\Framework\Rest\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Lomkit\Access\Controls\HasControl;
use Lomkit\Rest\Http\Requests\RestRequest;
use Lomkit\Rest\Http\Resource as RestResource;

abstract class Resource extends RestResource
{
    public function searchQuery(RestRequest $request, Builder $query): Builder
    {
        return $this->controlled($query);
    }

    public function destroyQuery(RestRequest $request, Builder $query): Builder
    {
        return $this->controlled($query);
    }

    public function restoreQuery(RestRequest $request, Builder $query): Builder
    {
        return $this->controlled($query);
    }

    public function forceDeleteQuery(RestRequest $request, Builder $query): Builder
    {
        return $this->controlled($query);
    }

    private function controlled(Builder $query): Builder
    {
        if (! in_array(HasControl::class, class_uses_recursive($query->getModel()), true)) {
            return $query;
        }

        return $query->controlled();
    }
}
