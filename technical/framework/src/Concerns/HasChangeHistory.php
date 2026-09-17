<?php

namespace Technical\Framework\Concerns;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Technical\Framework\Models\ChangeHistory;

/**
 * Declares the behavior on the model itself, so a reader of the model sees it right there
 * in the `use` clause — unlike an Observer, which reacts from a separate class registered
 * elsewhere and is invisible from the model alone.
 *
 * Using models must also `implements HistorizesChanges`.
 */
trait HasChangeHistory
{
    public static function bootHasChangeHistory(): void
    {
        static::updated(function (HistorizesChanges&Model $historizable): void {
            foreach ($historizable->historizedAttributes() as $attribute) {
                if (! $historizable->wasChanged($attribute)) {
                    continue;
                }

                $historizable->changeHistory()->create([
                    'attribute' => $attribute,
                    'old_value' => self::stringifyHistoryValue($historizable->getOriginal($attribute)),
                    'new_value' => self::stringifyHistoryValue($historizable->getAttribute($attribute)),
                    'user_id' => auth()->id(),
                ]);
            }
        });
    }

    /** @return MorphMany<ChangeHistory, $this> */
    public function changeHistory(): MorphMany
    {
        return $this->morphMany(ChangeHistory::class, 'historizable');
    }

    private static function stringifyHistoryValue(mixed $attributeValue): ?string
    {
        return match (true) {
            $attributeValue === null => null,
            $attributeValue instanceof BackedEnum => (string) $attributeValue->value,
            $attributeValue instanceof DateTimeInterface => $attributeValue->format(DATE_ATOM),
            default => (string) $attributeValue,
        };
    }
}
