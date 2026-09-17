<?php

namespace Functional\Tickets\Tests\Feature;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Mcp\Resources\TicketInputRulesResource;
use Functional\Tickets\Mcp\Servers\TicketsMcpServer;
use Functional\Tickets\Mcp\Tools\CreateTicketTool;
use Functional\Tickets\Mcp\Tools\SearchTicketsTool;
use Functional\Tickets\Mcp\Tools\ViewTicketTool;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketsMcpServerTest extends TestCase
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

    public function test_an_agent_can_create_a_ticket_through_the_same_action_as_the_form(): void
    {
        TicketsMcpServer::actingAs($this->requester)
            ->tool(CreateTicketTool::class, [
                'title' => 'VPN drops constantly',
                'description' => 'Connection resets every few minutes.',
                'priority' => 'high',
            ])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'title' => 'VPN drops constantly',
            'requester_id' => $this->requester->id,
        ]);
    }

    public function test_a_requester_can_only_search_their_own_tickets(): void
    {
        $other = User::factory()->create();
        Ticket::factory()->for($this->requester, 'requester')->create(['title' => 'Mine']);
        Ticket::factory()->for($other, 'requester')->create(['title' => 'Not mine']);

        TicketsMcpServer::actingAs($this->requester)
            ->tool(SearchTicketsTool::class)
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('Not mine');
    }

    public function test_viewing_a_ticket_the_agent_cannot_see_returns_an_explicit_error(): void
    {
        $other = User::factory()->create();
        $ticket = Ticket::factory()->for($other, 'requester')->create();

        TicketsMcpServer::actingAs($this->requester)
            ->tool(ViewTicketTool::class, ['ticket_id' => $ticket->id])
            ->assertHasErrors()
            ->assertSee((string) $ticket->id);
    }

    public function test_creating_a_ticket_without_permission_is_refused_with_an_explicit_error(): void
    {
        $noPermissionUser = User::factory()->create();

        TicketsMcpServer::actingAs($noPermissionUser)
            ->tool(CreateTicketTool::class, [
                'title' => 'Should not be created',
                'description' => 'Denied by policy.',
                'priority' => 'low',
            ])
            ->assertHasErrors();

        $this->assertDatabaseMissing('tickets', ['title' => 'Should not be created']);
    }

    public function test_the_input_rules_resource_lists_the_priority_values(): void
    {
        TicketsMcpServer::actingAs($this->requester)
            ->resource(TicketInputRulesResource::class)
            ->assertOk()
            ->assertSee(['low', 'normal', 'high', 'critical']);
    }
}
