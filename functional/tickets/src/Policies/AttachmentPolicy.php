<?php

namespace Functional\Tickets\Policies;

use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        return $user->can('view', $attachment->ticket);
    }

    public function create(User $user, Ticket $ticket): bool
    {
        return $user->can('view', $ticket);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return $user->is($attachment->uploader) && $user->can('view', $attachment->ticket);
    }
}
