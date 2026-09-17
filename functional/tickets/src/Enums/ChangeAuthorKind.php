<?php

namespace Functional\Tickets\Enums;

/**
 * Who a journal row is attributed to. A change made outside any session — a scheduled
 * command, a queued job, a seeder — has no user to point at, and recording that as an
 * empty author would read like missing data instead of the deliberate attribution it is.
 */
enum ChangeAuthorKind: string
{
    case User = 'user';
    case System = 'system';

    public function label(): string
    {
        return __("tickets::messages.change_history.author_kind.{$this->value}");
    }

    public static function forAuthorId(int|string|null $authorId): self
    {
        return $authorId === null ? self::System : self::User;
    }
}
