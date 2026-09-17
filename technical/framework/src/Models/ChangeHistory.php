<?php

namespace Technical\Framework\Models;

use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * A journal entry: written once by HasChangeHistory, never updated afterwards.
 *
 * @property int $id
 * @property class-string<Model> $historizable_type
 * @property int $historizable_id
 * @property string $attribute
 * @property string|null $old_value
 * @property string|null $new_value
 * @property int|null $user_id
 * @property Carbon $created_at
 * @property-read Model $historizable
 * @property-read User|null $user
 */
#[Fillable([
    'historizable_type',
    'historizable_id',
    'attribute',
    'old_value',
    'new_value',
    'user_id',
])]
class ChangeHistory extends Model
{
    use Prunable;

    private const RETENTION_DAYS = 365;

    /** @return MorphTo<Model, $this> */
    public function historizable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return Builder<ChangeHistory> */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(self::RETENTION_DAYS));
    }
}
