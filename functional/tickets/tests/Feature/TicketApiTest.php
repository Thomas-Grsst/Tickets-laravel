<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (TicketPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        Role::findOrCreate('requester')->syncPermissions([
            TicketPermission::CreateTicket->value,
            TicketPermission::ViewOwnTickets->value,
        ]);

        $this->requester = User::factory()->create();
        $this->requester->assignRole('requester');
    }

    public function test_a_requester_can_create_a_ticket_through_the_api(): void
    {
        Sanctum::actingAs($this->requester);

        $response = $this->postJson('/api/v1/tickets/mutate', [
            'mutate' => [[
                'operation' => 'create',
                'attributes' => [
                    'title' => 'Printer is on fire',
                    'description' => 'Smoke coming out of the third floor printer.',
                    'priority' => TicketPriority::High->value,
                ],
                'relations' => [
                    'requester' => ['operation' => 'attach', 'key' => $this->requester->id],
                ],
            ]],
        ]);

        $response->assertOk();
        $response->assertJsonPath('created.0', fn ($id) => is_int($id));

        $this->assertDatabaseHas('tickets', [
            'title' => 'Printer is on fire',
            'status' => TicketStatus::Open->value,
            'priority' => TicketPriority::High->value,
            'requester_id' => $this->requester->id,
        ]);
    }

    public function test_creating_a_ticket_rejects_a_prohibited_status_field(): void
    {
        Sanctum::actingAs($this->requester);

        $response = $this->postJson('/api/v1/tickets/mutate', [
            'mutate' => [[
                'operation' => 'create',
                'attributes' => [
                    'title' => 'Printer is on fire',
                    'description' => 'Smoke coming out of the third floor printer.',
                    'priority' => TicketPriority::High->value,
                    'status' => TicketStatus::Closed->value,
                ],
                'relations' => [
                    'requester' => ['operation' => 'attach', 'key' => $this->requester->id],
                ],
            ]],
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('tickets', ['title' => 'Printer is on fire']);
    }

    public function test_the_index_can_be_filtered_by_status(): void
    {
        Sanctum::actingAs($this->requester);

        Ticket::factory()->for($this->requester, 'requester')->create(['status' => TicketStatus::Open]);
        Ticket::factory()->for($this->requester, 'requester')->create(['status' => TicketStatus::Closed]);

        $response = $this->postJson('/api/v1/tickets/search', [
            'search' => [
                'filters' => [
                    ['field' => 'status', 'operator' => '=', 'value' => TicketStatus::Closed->value],
                ],
            ],
        ]);

        $response->assertOk();
        $tickets = $response->json('data');

        $this->assertCount(1, $tickets);
        $this->assertSame(TicketStatus::Closed->value, $tickets[0]['status']);
    }

    public function test_the_index_can_be_sorted_by_creation_date_descending(): void
    {
        Sanctum::actingAs($this->requester);

        $older = Ticket::factory()->for($this->requester, 'requester')->create(['created_at' => now()->subDays(2)]);
        $newer = Ticket::factory()->for($this->requester, 'requester')->create(['created_at' => now()]);

        $response = $this->postJson('/api/v1/tickets/search', [
            'search' => [
                'sorts' => [['field' => 'created_at', 'direction' => 'desc']],
            ],
        ]);

        $response->assertOk();
        $ids = array_column($response->json('data'), 'id');

        $this->assertSame([$newer->id, $older->id], $ids);
    }

    public function test_the_index_can_include_the_requester(): void
    {
        Sanctum::actingAs($this->requester);

        Ticket::factory()->for($this->requester, 'requester')->create();

        $response = $this->postJson('/api/v1/tickets/search', [
            'search' => [
                'includes' => [['relation' => 'requester']],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.0.requester.id', $this->requester->id);
    }

    public function test_a_manager_can_update_any_ticket(): void
    {
        Role::findOrCreate('manager')->syncPermissions([TicketPermission::ViewAllTickets->value]);
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        Sanctum::actingAs($manager);

        $ticket = Ticket::factory()->for($this->requester, 'requester')->create();

        $response = $this->postJson('/api/v1/tickets/mutate', [
            'mutate' => [[
                'operation' => 'update',
                'key' => $ticket->id,
                'attributes' => ['title' => 'Updated title'],
            ]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'title' => 'Updated title']);
    }

    public function test_a_requester_cannot_update_their_own_ticket_directly(): void
    {
        Sanctum::actingAs($this->requester);

        $ticket = Ticket::factory()->for($this->requester, 'requester')->create();

        $response = $this->postJson('/api/v1/tickets/mutate', [
            'mutate' => [[
                'operation' => 'update',
                'key' => $ticket->id,
                'attributes' => ['title' => 'Updated title'],
            ]],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id, 'title' => 'Updated title']);
    }
}
