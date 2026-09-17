<?php

namespace Functional\Tickets\Tests\Unit\Enums;

use Functional\Tickets\Enums\TicketPriority;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TicketPriorityTest extends TestCase
{
    #[Test]
    #[DataProvider('slaTargets')]
    public function it_returns_the_sla_target_in_hours_for_a_priority(TicketPriority $priority, int $expectedHours): void
    {
        $this->assertSame($expectedHours, $priority->slaHours());
    }

    /**
     * @return array<string, array{TicketPriority, int}>
     */
    public static function slaTargets(): array
    {
        return [
            'low'      => [TicketPriority::Low, 72],
            'normal'   => [TicketPriority::Normal, 24],
            'high'     => [TicketPriority::High, 8],
            'critical' => [TicketPriority::Critical, 2],
        ];
    }

    #[Test]
    public function it_tightens_the_sla_target_as_the_priority_rises(): void
    {
        $this->assertGreaterThan(TicketPriority::Normal->slaHours(), TicketPriority::Low->slaHours());
        $this->assertGreaterThan(TicketPriority::High->slaHours(), TicketPriority::Normal->slaHours());
        $this->assertGreaterThan(TicketPriority::Critical->slaHours(), TicketPriority::High->slaHours());
    }
}
