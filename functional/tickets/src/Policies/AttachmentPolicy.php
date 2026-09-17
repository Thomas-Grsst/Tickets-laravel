<?php

namespace Functional\Tickets\Policies;

use Functional\Tickets\Models\Attachment;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;

class AttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny', Ticket::class);
    }

    public function view(User $user, Attachment $attachment): bool
    {
        return $user->can('view', $attachment->ticket);
    }

    /**
     * Attaching a file is part of taking part in the conversation, so it follows the
     * perimeter that decides who reaches the ticket at all.
     */
    public function create(User $user, Ticket $ticket): bool
    {
        return $user->can('view', $ticket);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return $user->is($attachment->uploader) && $user->can('view', $attachment->ticket);
    }
}
