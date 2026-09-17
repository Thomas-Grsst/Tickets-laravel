<?php

namespace Functional\Tickets\Tests\Unit;

use Functional\Tickets\Enums\TicketPriority;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketPriorityTest extends TestCase
{
    /**
     * @return iterable<string, array{TicketPriority, int}>
     */
    public static function slaHoursProvider(): iterable
    {
        yield 'low' => [TicketPriority::Low, 72];
        yield 'normal' => [TicketPriority::Normal, 24];
        yield 'high' => [TicketPriority::High, 8];
        yield 'critical' => [TicketPriority::Critical, 2];
    }

    #[DataProvider('slaHoursProvider')]
    public function test_each_priority_carries_its_target_sla_in_hours(TicketPriority $priority, int $hours): void
    {
        $this->assertSame($hours, $priority->slaHours());
    }
}
