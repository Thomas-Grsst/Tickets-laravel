<?php

namespace Functional\Tickets\Policies;

use Functional\Tickets\Models\Comment;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;

class CommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny', Ticket::class);
    }

    public function view(User $user, Comment $comment): bool
    {
        return $user->can('view', $comment->ticket);
    }

    public function create(User $user): bool
    {
        return $user->can('viewAny', Ticket::class);
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->is($comment->author) && $user->can('view', $comment->ticket);
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->is($comment->author) && $user->can('view', $comment->ticket);
    }
}
