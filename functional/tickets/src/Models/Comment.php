<?php

namespace Functional\Tickets\Models;

use Functional\Tickets\Database\Factories\CommentFactory;
use Functional\Tickets\Models\Concerns\HistorizesChanges;
use Functional\Tickets\Policies\CommentPolicy;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ticket_id',
    'author_id',
    'body',
])]
#[UseFactory(CommentFactory::class)]
#[UsePolicy(CommentPolicy::class)]
class Comment extends Model
{
    use HasFactory;
    use HistorizesChanges;

    /**
     * A comment is its text. An edited comment changes what the ticket thread says
     * happened, so the wording it replaced has to remain readable.
     *
     * @return list<string>
     */
    public function historizedAttributes(): array
    {
        return ['body'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
