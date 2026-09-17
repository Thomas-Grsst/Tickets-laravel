<?php

namespace Functional\Tickets\Tests\Feature\Broadcasting;

use Functional\Tickets\Broadcasting\TicketChannel;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The security control of the realtime feature: a subscription to a ticket's private
 * channel is granted by the same perimeters that decide whether the ticket appears in the
 * list, so a user outside a ticket's perimeter is refused the channel and therefore never
 * receives a single message about it.
 */
class TicketChannelAuthorizationTest extends TestCase
{
    use CreatesTicketProfiles;
    use RefreshDatabase;

    /**
     * The suite runs on the `null` broadcaster, which authorises nothing and would let every
     * assertion below pass for the wrong reason. Channels are registered on the connection
     * the application boots with, so the Pusher connection — the one Soketi serves — is put
     * in place before the application is created rather than swapped in afterwards.
     *
     * @var array<string, string>
     */
    private const BROADCASTING_ENVIRONMENT = [
        'BROADCAST_CONNECTION' => 'pusher',
        'PUSHER_APP_ID' => 'tickets-test',
        'PUSHER_APP_KEY' => 'tickets-test-key',
        'PUSHER_APP_SECRET' => 'tickets-test-secret',
        'PUSHER_APP_CLUSTER' => 'mt1',
        'PUSHER_HOST' => 'soketi',
        'PUSHER_PORT' => '6001',
        'PUSHER_SCHEME' => 'http',
    ];

    protected function setUp(): void
    {
        foreach (self::BROADCASTING_ENVIRONMENT as $environmentVariable => $setting) {
            putenv($environmentVariable . '=' . $setting);
            $_ENV[$environmentVariable] = $setting;
            $_SERVER[$environmentVariable] = $setting;
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        foreach (array_keys(self::BROADCASTING_ENVIRONMENT) as $environmentVariable) {
            putenv($environmentVariable);
            unset($_ENV[$environmentVariable], $_SERVER[$environmentVariable]);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_lets_a_requester_listen_to_a_ticket_they_opened(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create();

        $this->actingAs($requester)
            ->postJson('/broadcasting/auth', $this->subscriptionTo($ticket))
            ->assertSuccessful();
    }

    #[Test]
    public function it_refuses_a_requester_the_channel_of_a_ticket_someone_else_opened(): void
    {
        $requester = $this->createRequester();
        $foreign = Ticket::factory()->create();

        $this->actingAs($requester)
            ->postJson('/broadcasting/auth', $this->subscriptionTo($foreign))
            ->assertForbidden();
    }

    #[Test]
    public function it_lets_a_technician_listen_to_a_ticket_assigned_to_them(): void
    {
        $technician = $this->createTechnician();
        $ticket = Ticket::factory()->for($technician, 'assignedTechnician')->create();

        $this->actingAs($technician)
            ->postJson('/broadcasting/auth', $this->subscriptionTo($ticket))
            ->assertSuccessful();
    }

    #[Test]
    public function it_refuses_a_technician_the_channel_of_a_ticket_assigned_to_someone_else(): void
    {
        $technician = $this->createTechnician();
        $ticket = Ticket::factory()->for($this->createTechnician(), 'assignedTechnician')->create();

        $this->actingAs($technician)
            ->postJson('/broadcasting/auth', $this->subscriptionTo($ticket))
            ->assertForbidden();
    }

    #[Test]
    public function it_lets_a_manager_listen_to_any_ticket(): void
    {
        $manager = $this->createManager();
        $ticket = Ticket::factory()->create();

        $this->actingAs($manager)
            ->postJson('/broadcasting/auth', $this->subscriptionTo($ticket))
            ->assertSuccessful();
    }

    #[Test]
    public function it_refuses_the_channel_to_a_user_holding_no_ticket_permission(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs(User::factory()->create())
            ->postJson('/broadcasting/auth', $this->subscriptionTo($ticket))
            ->assertForbidden();
    }

    #[Test]
    public function it_refuses_the_channel_to_a_guest(): void
    {
        $ticket = Ticket::factory()->create();

        $this->postJson('/broadcasting/auth', $this->subscriptionTo($ticket))
            ->assertForbidden();
    }

    #[Test]
    public function it_refuses_a_channel_naming_a_ticket_that_does_not_exist(): void
    {
        $manager = $this->createManager();

        $this->actingAs($manager)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-tickets.404',
                'socket_id' => '1234.5678',
            ])
            ->assertForbidden();
    }

    /**
     * @return array<string, string>
     */
    private function subscriptionTo(Ticket $ticket): array
    {
        return [
            'channel_name' => TicketChannel::for($ticket)->name,
            'socket_id' => '1234.5678',
        ];
    }
}
