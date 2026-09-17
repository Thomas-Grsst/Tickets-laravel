<?php

namespace Functional\Tickets\Tests\Feature\Notifying;

use Functional\Tickets\Actions\AssignTicket;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Mail\TicketNeedsHandlingMail;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Notifications\TicketAssignedNotification;
use Functional\Tickets\Notifications\TicketNeedsHandlingNotification;
use Functional\Tickets\Notifying\Channels\ImmediateAlertTicketChannel;
use Functional\Tickets\Notifying\Channels\UrgentTicketChannel;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Drives the real path — assign a ticket, let the listener resolve the tier — and asserts
 * the exact channel set each priority earns. The two simulated channels are read back off
 * the log records they emit.
 */
class TicketNotificationPolicyTest extends TestCase
{
    use CreatesTicketProfiles;
    use RefreshDatabase;

    /** @var list<MessageLogged> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();

        Event::listen(function (MessageLogged $entry): void {
            $this->logged[] = $entry;
        });
    }

    #[Test]
    public function it_only_mails_the_managers_for_a_low_priority_ticket(): void
    {
        $manager = $this->assignTicketWithPriority(TicketPriority::Low);

        Notification::assertSentTo($manager, TicketNeedsHandlingNotification::class);
        $this->assertSame([], $this->recordsFor(UrgentTicketChannel::NAME));
        $this->assertSame([], $this->recordsFor(ImmediateAlertTicketChannel::NAME));
    }

    #[Test]
    public function it_only_mails_the_managers_for_a_normal_priority_ticket(): void
    {
        $manager = $this->assignTicketWithPriority(TicketPriority::Normal);

        Notification::assertSentTo($manager, TicketNeedsHandlingNotification::class);
        $this->assertSame([], $this->recordsFor(UrgentTicketChannel::NAME));
        $this->assertSame([], $this->recordsFor(ImmediateAlertTicketChannel::NAME));
    }

    #[Test]
    public function it_adds_the_urgent_channel_for_a_high_priority_ticket(): void
    {
        $manager = $this->assignTicketWithPriority(TicketPriority::High);

        Notification::assertSentTo($manager, TicketNeedsHandlingNotification::class);
        $this->assertUrgentChannelAnnounced(TicketPriority::High);
        $this->assertSame([], $this->recordsFor(ImmediateAlertTicketChannel::NAME));
    }

    #[Test]
    public function it_adds_the_urgent_channel_and_an_immediate_manager_alert_for_a_critical_ticket(): void
    {
        $manager = $this->assignTicketWithPriority(TicketPriority::Critical);

        Notification::assertSentTo($manager, TicketNeedsHandlingNotification::class);
        $this->assertUrgentChannelAnnounced(TicketPriority::Critical);
        $this->assertManagersPaged($manager);
    }

    #[Test]
    public function it_leaves_the_technician_assignment_mail_untouched(): void
    {
        $manager = $this->assignTicketWithPriority(TicketPriority::Critical);

        Notification::assertNotSentTo($manager, TicketAssignedNotification::class);
        Notification::assertCount(2);
    }

    #[Test]
    public function it_builds_the_handling_mail_from_the_notification(): void
    {
        $ticket = Ticket::factory()->create(['priority' => TicketPriority::High]);

        $mailable = (new TicketNeedsHandlingNotification($ticket))->toMail($this->createManager());

        $this->assertInstanceOf(TicketNeedsHandlingMail::class, $mailable);
        $mailable->assertSeeInHtml($ticket->title);
        $mailable->assertSeeInHtml((string) $ticket->priority->slaHours());
    }

    /**
     * Assigns a ticket of the given priority and hands back the single manager the policy
     * is expected to reach.
     */
    private function assignTicketWithPriority(TicketPriority $priority): User
    {
        $manager = $this->createManager();
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'priority' => $priority,
            'status' => TicketStatus::Open,
        ]);

        app(AssignTicket::class)($ticket, $this->createTechnician());

        return $manager;
    }

    private function assertUrgentChannelAnnounced(TicketPriority $priority): void
    {
        $records = $this->recordsFor(UrgentTicketChannel::NAME);

        $this->assertCount(1, $records);
        $this->assertSame('warning', $records[0]->level);
        $this->assertSame($priority->value, $records[0]->context['priority']);
    }

    private function assertManagersPaged(User $manager): void
    {
        $records = $this->recordsFor(ImmediateAlertTicketChannel::NAME);

        $this->assertCount(1, $records);
        $this->assertSame('critical', $records[0]->level);
        $this->assertSame([$manager->getKey()], $records[0]->context['recipient_ids']);
    }

    /**
     * @return list<MessageLogged>
     */
    private function recordsFor(string $channel): array
    {
        return array_values(array_filter(
            $this->logged,
            static fn (MessageLogged $entry): bool => ($entry->context['channel'] ?? null) === $channel,
        ));
    }
}
