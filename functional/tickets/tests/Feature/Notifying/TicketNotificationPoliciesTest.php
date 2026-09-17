<?php

namespace Functional\Tickets\Tests\Feature\Notifying;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Exceptions\TicketNotificationPolicyMissingException;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Notifying\HandlesTicketPriority;
use Functional\Tickets\Notifying\Policies\CriticalPriorityNotificationPolicy;
use Functional\Tickets\Notifying\Policies\HighPriorityNotificationPolicy;
use Functional\Tickets\Notifying\Policies\LowPriorityNotificationPolicy;
use Functional\Tickets\Notifying\Policies\NormalPriorityNotificationPolicy;
use Functional\Tickets\Notifying\TicketNotificationPolicies;
use Functional\Tickets\Notifying\TicketNotificationPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketNotificationPoliciesTest extends TestCase
{
    private const EXTRA_POLICY = 'tests.extra-notification-policy';

    #[Test]
    #[DataProvider('shippedTiers')]
    public function it_resolves_the_tier_registered_for_a_priority(TicketPriority $priority, string $expectedPolicy): void
    {
        $this->assertInstanceOf(
            $expectedPolicy,
            app(TicketNotificationPolicies::class)->for($priority),
        );
    }

    /**
     * @return array<string, array{TicketPriority, class-string<TicketNotificationPolicy>}>
     */
    public static function shippedTiers(): array
    {
        return [
            'low' => [TicketPriority::Low, LowPriorityNotificationPolicy::class],
            'normal' => [TicketPriority::Normal, NormalPriorityNotificationPolicy::class],
            'high' => [TicketPriority::High, HighPriorityNotificationPolicy::class],
            'critical' => [TicketPriority::Critical, CriticalPriorityNotificationPolicy::class],
        ];
    }

    /**
     * The claim behind "a new priority costs one class plus one tag entry": a tier tagged
     * after the layer booted is resolved without a single edit to the resolver, the
     * listener, or any shipped tier.
     */
    #[Test]
    public function it_resolves_a_tier_tagged_after_the_shipped_ones(): void
    {
        $tier = $this->buildTierFor(TicketPriority::Low);
        $this->app->bind(self::EXTRA_POLICY, static fn (): object => $tier);
        $this->app->tag([self::EXTRA_POLICY], TicketNotificationPolicies::TAG);

        $resolved = app(TicketNotificationPolicies::class)->for(TicketPriority::Low);

        $this->assertSame($tier, $resolved);
    }

    #[Test]
    public function it_refuses_a_priority_no_tier_claims(): void
    {
        $policies = new TicketNotificationPolicies([]);

        $this->assertThrows(
            static fn () => $policies->for(TicketPriority::Critical),
            TicketNotificationPolicyMissingException::class,
        );
    }

    private function buildTierFor(TicketPriority $priority): TicketNotificationPolicy&HandlesTicketPriority
    {
        return new class ($priority) implements HandlesTicketPriority, TicketNotificationPolicy {
            public function __construct(private readonly TicketPriority $priority)
            {
            }

            public function handles(): TicketPriority
            {
                return $this->priority;
            }

            public function notify(Ticket $ticket): void
            {
            }
        };
    }
}
