<?php

namespace Functional\Tickets\Models;

use Carbon\CarbonInterface;
use Functional\Tickets\Enums\ChangeAuthorKind;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One row per historized attribute that actually changed. The table is a journal: rows are
 * appended by HistorizesChanges and read back, never edited — which is why it carries a
 * `created_at` and no `updated_at`, and why no write path other than the trait exists.
 *
 * @property int              $id
 * @property string           $historizable_type
 * @property int              $historizable_id
 * @property string           $attribute
 * @property ?string          $old_value
 * @property ?string          $new_value
 * @property ?int             $author_id
 * @property ChangeAuthorKind $author_kind
 * @property CarbonInterface  $created_at
 */
#[Fillable([
    'attribute',
    'old_value',
    'new_value',
    'author_id',
    'author_kind',
])]
class ChangeHistory extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    private const RETENTION_DAYS = 365;

    protected function casts(): array
    {
        return [
            'author_kind' => ChangeAuthorKind::class,
        ];
    }

    public function historizable(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(self::RETENTION_DAYS));
    }
}
