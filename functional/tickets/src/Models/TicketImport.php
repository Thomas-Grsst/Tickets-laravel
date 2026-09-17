<?php

namespace Functional\Tickets\Models;

use Functional\Tickets\Database\Factories\TicketImportFactory;
use Functional\Tickets\Enums\TicketImportStatus;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $uploaded_by
 * @property string $original_name
 * @property TicketImportStatus $status
 * @property int|null $rows_read
 * @property int|null $tickets_created
 * @property list<array{line: int, reason: string}>|null $rejections
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $uploader
 */
#[Fillable([
    'uploaded_by',
    'original_name',
    'status',
    'rows_read',
    'tickets_created',
    'rejections',
])]
#[UseFactory(TicketImportFactory::class)]
class TicketImport extends Model
{
    /** @use HasFactory<TicketImportFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketImportStatus::class,
            'rejections' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
