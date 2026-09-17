<?php

namespace Functional\Tickets\Tests\Unit;

use Functional\Tickets\Enums\TicketStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketStatusTest extends TestCase
{
    /**
     * @return iterable<string, array{TicketStatus, list<TicketStatus>}>
     */
    public static function legalTransitionsProvider(): iterable
    {
        yield 'open' => [TicketStatus::Open, [TicketStatus::Assigned]];
        yield 'assigned' => [TicketStatus::Assigned, [TicketStatus::InProgress, TicketStatus::Open]];
        yield 'in progress' => [TicketStatus::InProgress, [TicketStatus::Resolved, TicketStatus::Assigned]];
        yield 'resolved' => [TicketStatus::Resolved, [TicketStatus::Closed, TicketStatus::InProgress]];
        yield 'closed' => [TicketStatus::Closed, []];
    }

    /**
     * @param  list<TicketStatus>  $legalTargets
     */
    #[DataProvider('legalTransitionsProvider')]
    public function test_it_only_allows_the_transitions_from_the_lifecycle_table(TicketStatus $from, array $legalTargets): void
    {
        foreach (TicketStatus::cases() as $target) {
            $this->assertSame(
                in_array($target, $legalTargets, true),
                $from->canTransitionTo($target),
                "{$from->value} -> {$target->value}",
            );
        }
    }

    public function test_only_closed_is_terminal(): void
    {
        foreach (TicketStatus::cases() as $status) {
            $this->assertSame($status === TicketStatus::Closed, $status->isTerminal());
        }
    }
}
