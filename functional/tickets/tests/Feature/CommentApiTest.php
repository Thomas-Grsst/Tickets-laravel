<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommentApiTest extends TestCase
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

    public function test_a_comment_can_be_added_to_a_ticket_through_the_api(): void
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
                'relations' => [
                    'comments' => [
                        [
                            'operation' => 'create',
                            'attributes' => ['body' => 'Any update on this?'],
                            'relations' => ['author' => ['operation' => 'attach', 'key' => $manager->id]],
                        ],
                    ],
                ],
            ]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('comments', [
            'ticket_id' => $ticket->id,
            'body' => 'Any update on this?',
        ]);
    }

    public function test_the_ticket_can_be_read_with_its_comment_count(): void
    {
        Sanctum::actingAs($this->requester);

        $ticket = Ticket::factory()->for($this->requester, 'requester')->create();
        $ticket->comments()->create(['body' => 'First', 'author_id' => $this->requester->id]);
        $ticket->comments()->create(['body' => 'Second', 'author_id' => $this->requester->id]);

        $response = $this->postJson('/api/v1/tickets/search', [
            'search' => [
                'includes' => [['relation' => 'comments']],
            ],
        ]);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.0.comments'));
    }
}
