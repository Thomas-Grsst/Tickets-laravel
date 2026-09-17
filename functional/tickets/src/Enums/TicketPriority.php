<?php

namespace Functional\Tickets\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Critical = 'critical';

    public function slaHours(): int
    {
        return match ($this) {
            self::Low => 72,
            self::Normal => 24,
            self::High => 8,
            self::Critical => 2,
        };
    }

    /**
     * The next priority up the escalation ladder, or null once already at the ceiling.
     */
    public function escalated(): ?self
    {
        return match ($this) {
            self::Low => self::Normal,
            self::Normal => self::High,
            self::High => self::Critical,
            self::Critical => null,
        };
    }
}
