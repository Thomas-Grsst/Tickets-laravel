<?php

namespace Functional\Tickets\Tests\Feature\Api;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateTicketTest extends TestCase
{
    use CreatesTicketProfiles;
    use RefreshDatabase;

    #[Test]
    public function it_persists_a_ticket_created_through_the_mutate_endpoint(): void
    {
        $requester = $this->createRequester();
        Sanctum::actingAs($requester);

        $response = $this->postJson('/api/v1/tickets/mutate', [
            'mutate' => [[
                'operation' => 'create',
                'attributes' => [
                    'title' => 'Printer on the second floor is jammed',
                    'description' => 'It stops mid-page and reports a paper jam that is not there.',
                    'priority' => TicketPriority::High->value,
                ],
                'relations' => [
                    'requester' => ['operation' => 'attach', 'key' => $requester->getKey()],
                ],
            ]],
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('tickets', [
            'title' => 'Printer on the second floor is jammed',
            'requester_id' => $requester->getKey(),
            'priority' => TicketPriority::High->value,
            'status' => TicketStatus::Open->value,
        ]);
    }

    #[Test]
    public function it_opens_every_new_ticket_without_a_technician(): void
    {
        $requester = $this->createRequester();
        Sanctum::actingAs($requester);

        $this->postJson('/api/v1/tickets/mutate', [
            'mutate' => [[
                'operation' => 'create',
                'attributes' => [
                    'title' => 'VPN drops every ten minutes',
                    'description' => 'The tunnel reconnects on its own but the session is lost.',
                    'priority' => TicketPriority::Normal->value,
                ],
                'relations' => [
                    'requester' => ['operation' => 'attach', 'key' => $requester->getKey()],
                ],
            ]],
        ])->assertSuccessful();

        $this->assertDatabaseHas('tickets', [
            'title' => 'VPN drops every ten minutes',
            'status' => TicketStatus::Open->value,
            'assigned_technician_id' => null,
            'resolved_at' => null,
            'sla_met' => null,
        ]);
    }

    #[Test]
    public function it_rejects_a_ticket_created_without_a_title(): void
    {
        $requester = $this->createRequester();
        Sanctum::actingAs($requester);

        $this->postJson('/api/v1/tickets/mutate', [
            'mutate' => [[
                'operation' => 'create',
                'attributes' => [
                    'description' => 'No title on this one.',
                    'priority' => TicketPriority::Low->value,
                ],
                'relations' => [
                    'requester' => ['operation' => 'attach', 'key' => $requester->getKey()],
                ],
            ]],
        ])->assertStatus(422);

        $this->assertDatabaseCount('tickets', 0);
    }

    #[Test]
    public function it_rejects_a_client_that_tries_to_choose_the_status_itself(): void
    {
        $requester = $this->createRequester();
        Sanctum::actingAs($requester);

        $this->postJson('/api/v1/tickets/mutate', [
            'mutate' => [[
                'operation' => 'create',
                'attributes' => [
                    'title' => 'Already resolved, honest',
                    'description' => 'Trying to write the lifecycle output directly.',
                    'priority' => TicketPriority::Low->value,
                    'status' => TicketStatus::Resolved->value,
                ],
                'relations' => [
                    'requester' => ['operation' => 'attach', 'key' => $requester->getKey()],
                ],
            ]],
        ])->assertStatus(422);

        $this->assertDatabaseCount('tickets', 0);
    }

    #[Test]
    public function it_refuses_an_unauthenticated_creation(): void
    {
        $this->postJson('/api/v1/tickets/mutate', [
            'mutate' => [[
                'operation' => 'create',
                'attributes' => [
                    'title' => 'From nobody',
                    'description' => 'No token on this request.',
                    'priority' => TicketPriority::Low->value,
                ],
            ]],
        ])->assertUnauthorized();

        $this->assertDatabaseCount('tickets', 0);
    }
}
