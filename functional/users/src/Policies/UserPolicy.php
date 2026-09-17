<?php

namespace Functional\Users\Policies;

use Functional\Users\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, User $viewed): bool
    {
        return true;
    }
}
