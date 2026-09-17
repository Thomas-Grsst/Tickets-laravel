<?php

namespace Functional\Tickets\Enums;

enum TicketStatus: string
{
    case Open       = 'open';
    case Assigned   = 'assigned';
    case InProgress = 'in_progress';
    case Resolved   = 'resolved';
    case Closed     = 'closed';

    public function isTerminal(): bool
    {
        return $this === self::Closed;
    }

    /**
     * The whole lifecycle in one place: anything absent from this table is illegal,
     * including staying on the same status.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open       => [self::Assigned],
            self::Assigned   => [self::InProgress, self::Open],
            self::InProgress => [self::Resolved, self::Assigned],
            self::Resolved   => [self::Closed, self::InProgress],
            self::Closed     => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
