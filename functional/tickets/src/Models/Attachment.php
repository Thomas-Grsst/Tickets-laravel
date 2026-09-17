<?php

namespace Functional\Tickets\Models;

use Carbon\CarbonInterface;
use Functional\Tickets\Database\Factories\AttachmentFactory;
use Functional\Tickets\Enums\AttachmentKind;
use Functional\Tickets\Policies\AttachmentPolicy;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int              $id
 * @property int              $ticket_id
 * @property int              $uploaded_by_id
 * @property string           $name
 * @property string           $path
 * @property AttachmentKind   $kind
 * @property string           $mime_type
 * @property int              $size_in_bytes
 * @property CarbonInterface  $created_at
 * @property CarbonInterface  $updated_at
 */
#[Fillable([
    'ticket_id',
    'uploaded_by_id',
    'name',
    'path',
    'kind',
    'mime_type',
    'size_in_bytes',
])]
#[UseFactory(AttachmentFactory::class)]
#[UsePolicy(AttachmentPolicy::class)]
class Attachment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kind' => AttachmentKind::class,
            'size_in_bytes' => 'integer',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
