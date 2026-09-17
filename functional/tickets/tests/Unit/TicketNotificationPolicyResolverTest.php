<?php

namespace Functional\Tickets\Tests\Unit;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Notifications\Channels\CriticalTicketNotificationPolicy;
use Functional\Tickets\Notifications\Channels\StandardTicketNotificationPolicy;
use Functional\Tickets\Notifications\Channels\TicketNotificationPolicyResolver;
use Functional\Tickets\Notifications\Channels\UrgentTicketNotificationPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketNotificationPolicyResolverTest extends TestCase
{
    /**
     * @return iterable<string, array{TicketPriority, class-string, list<string>}>
     */
    public static function policiesProvider(): iterable
    {
        yield 'low' => [TicketPriority::Low, StandardTicketNotificationPolicy::class, ['mail']];
        yield 'normal' => [TicketPriority::Normal, StandardTicketNotificationPolicy::class, ['mail']];
        yield 'high' => [TicketPriority::High, UrgentTicketNotificationPolicy::class, ['mail', 'urgent']];
        yield 'critical' => [TicketPriority::Critical, CriticalTicketNotificationPolicy::class, ['mail', 'urgent', 'immediate-alert']];
    }

    /**
     * @param  class-string  $expectedPolicy
     * @param  list<string>  $expectedChannels
     */
    #[DataProvider('policiesProvider')]
    public function test_it_resolves_the_policy_and_channels_for_each_priority(
        TicketPriority $priority,
        string $expectedPolicy,
        array $expectedChannels,
    ): void {
        $policy = (new TicketNotificationPolicyResolver)->resolve($priority);

        $this->assertInstanceOf($expectedPolicy, $policy);
        $this->assertSame($expectedChannels, $policy->channels());
    }
}
