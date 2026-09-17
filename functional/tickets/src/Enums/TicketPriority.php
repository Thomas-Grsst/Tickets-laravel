<?php

namespace Functional\Tickets\Enums;

enum TicketPriority: string
{
    case Low      = 'low';
    case Normal   = 'normal';
    case High     = 'high';
    case Critical = 'critical';

    public function slaHours(): int
    {
        return match ($this) {
            self::Low      => 72,
            self::Normal   => 24,
            self::High     => 8,
            self::Critical => 2,
        };
    }

    /**
     * The single rung an overdue ticket climbs. Critical is the top of the ladder, so it
     * returns null and the caller reports the breach instead of raising the priority.
     */
    public function escalatesTo(): ?self
    {
        return match ($this) {
            self::Low      => self::Normal,
            self::Normal   => self::High,
            self::High     => self::Critical,
            self::Critical => null,
        };
    }
}
