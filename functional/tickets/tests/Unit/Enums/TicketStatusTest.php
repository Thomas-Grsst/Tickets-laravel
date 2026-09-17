<?php

namespace Functional\Tickets\Tests\Unit\Enums;

use Functional\Tickets\Enums\TicketStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TicketStatusTest extends TestCase
{
    /**
     * The lifecycle spelled out independently of the enum, so the matrix below fails when
     * the table changes instead of agreeing with whatever it now says.
     *
     * @var array<string, list<string>>
     */
    private const LEGAL_TRANSITIONS = [
        'open'        => ['assigned'],
        'assigned'    => ['in_progress', 'open'],
        'in_progress' => ['resolved', 'assigned'],
        'resolved'    => ['closed', 'in_progress'],
        'closed'      => [],
    ];

    #[Test]
    #[DataProvider('transitionMatrix')]
    public function it_answers_the_whole_transition_matrix(TicketStatus $from, TicketStatus $target, bool $expected): void
    {
        $this->assertSame($expected, $from->canTransitionTo($target));
    }

    /**
     * Every ordered pair of statuses, including each status to itself.
     *
     * @return array<string, array{TicketStatus, TicketStatus, bool}>
     */
    public static function transitionMatrix(): array
    {
        $cases = [];

        foreach (TicketStatus::cases() as $from) {
            foreach (TicketStatus::cases() as $target) {
                $expected = in_array($target->value, self::LEGAL_TRANSITIONS[$from->value], true);
                $label = sprintf('%s to %s is %s', $from->value, $target->value, $expected ? 'legal' : 'illegal');

                $cases[$label] = [$from, $target, $expected];
            }
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('transitionMatrix')]
    public function it_lists_exactly_the_targets_it_allows(TicketStatus $from, TicketStatus $target, bool $expected): void
    {
        $this->assertSame($expected, in_array($target, $from->allowedTransitions(), true));
    }

    #[Test]
    public function it_treats_only_the_closed_status_as_terminal(): void
    {
        foreach (TicketStatus::cases() as $status) {
            $this->assertSame($status === TicketStatus::Closed, $status->isTerminal());
        }
    }

    #[Test]
    public function it_leaves_no_way_out_of_a_terminal_status(): void
    {
        $this->assertSame([], TicketStatus::Closed->allowedTransitions());
    }

    #[Test]
    public function it_never_allows_a_status_to_transition_to_itself(): void
    {
        foreach (TicketStatus::cases() as $status) {
            $this->assertFalse($status->canTransitionTo($status));
        }
    }
}
