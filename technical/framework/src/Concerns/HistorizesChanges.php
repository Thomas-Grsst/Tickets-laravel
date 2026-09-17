<?php

namespace Technical\Framework\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Satisfied by any model using the HasChangeHistory trait — split out so the trait's own
 * boot closure has a concrete type to call `historizedAttributes()` and `changeHistory()`
 * on, since a trait name can't appear in a type intersection.
 */
interface HistorizesChanges
{
    /** @return list<string> */
    public function historizedAttributes(): array;

    /**
     * Left ungenerified on purpose: the concrete return type is `MorphMany<ChangeHistory,
     * $this>` on every implementer, and PHPStan treats MorphMany's declaring-model
     * parameter as invariant, so no single generic annotation here is compatible with all
     * of them at once.
     *
     * @phpstan-ignore missingType.generics
     */
    public function changeHistory(): MorphMany;
}
