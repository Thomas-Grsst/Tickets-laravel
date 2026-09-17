<?php

namespace Functional\Tickets\Models\Concerns;

use BackedEnum;
use Carbon\CarbonInterface;
use Functional\Tickets\Enums\ChangeAuthorKind;
use Functional\Tickets\Models\ChangeHistory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

/**
 * Appends a journal row per historized attribute whose value actually changed.
 *
 * The behaviour is declared by the model that wants it — `use HistorizesChanges` plus the
 * list of attributes worth remembering — so a reader who opens the model sees the whole
 * contract there. The trait knows nothing about any particular model; a new historizable
 * model is the two lines below and nothing else.
 */
trait HistorizesChanges
{
    /**
     * The attributes whose changes carry information someone will want to read back.
     * Declared per model on purpose: historizing everything buries the meaningful rows
     * under `updated_at` noise.
     *
     * @return list<string>
     */
    abstract public function historizedAttributes(): array;

    /**
     * The Eloquent trait-boot convention, the same mechanism SoftDeletes uses: declaring
     * the trait is what wires the hook, so there is nothing to register elsewhere.
     */
    public static function bootHistorizesChanges(): void
    {
        static::updated(static function (self $historizable): void {
            $historizable->recordHistorizedChanges();
        });
    }

    /** @return MorphMany<ChangeHistory, $this> */
    public function changeHistories(): MorphMany
    {
        return $this->morphMany(ChangeHistory::class, 'historizable')->latest('id');
    }

    /**
     * Runs inside the `updated` event, where `getOriginal()` still holds the pre-save
     * values and `wasChanged()` already holds the applied diff.
     */
    private function recordHistorizedChanges(): void
    {
        $authorId = Auth::id();
        $journalRows = [];

        foreach ($this->historizedAttributes() as $attribute) {
            if (! $this->wasChanged($attribute)) {
                continue;
            }

            $journalRows[] = [
                'attribute' => $attribute,
                'old_value' => $this->stringifyHistorizedValue($this->getOriginal($attribute)),
                'new_value' => $this->stringifyHistorizedValue($this->getAttribute($attribute)),
                'author_id' => $authorId,
                'author_kind' => ChangeAuthorKind::forAuthorId($authorId),
            ];
        }

        if ($journalRows === []) {
            return;
        }

        $this->changeHistories()->createMany($journalRows);
    }

    /**
     * The journal stores what the attribute read as, not the object it was cast to, so a
     * row stays readable after the enum case or the cast behind it is renamed.
     */
    private function stringifyHistorizedValue(mixed $historizedValue): ?string
    {
        return match (true) {
            $historizedValue === null => null,
            $historizedValue instanceof BackedEnum => (string) $historizedValue->value,
            $historizedValue instanceof CarbonInterface => $historizedValue->toIso8601String(),
            is_scalar($historizedValue) => (string) $historizedValue,
            default => json_encode($historizedValue) ?: null,
        };
    }
}
