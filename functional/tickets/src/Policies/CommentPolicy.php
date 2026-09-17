<?php

namespace Functional\Tickets\Policies;

use Functional\Tickets\Models\Comment;
use Functional\Users\Models\User;

class CommentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Comment $comment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Comment $comment): bool
    {
        return true;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return true;
    }
}
