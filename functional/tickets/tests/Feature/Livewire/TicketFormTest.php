<?php

namespace Functional\Tickets\Tests\Feature\Livewire;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Livewire\TicketForm;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketFormTest extends TestCase
{
    use RefreshDatabase;

    private User $requester;

    private User $manager;

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

        Role::findOrCreate('manager')->syncPermissions([
            TicketPermission::ViewAllTickets->value,
            TicketPermission::AssignTicket->value,
            TicketPermission::CloseTicket->value,
        ]);

        $this->requester = User::factory()->create();
        $this->requester->assignRole('requester');

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');
    }

    public function test_a_requester_can_create_a_ticket(): void
    {
        Livewire::actingAs($this->requester)
            ->test(TicketForm::class)
            ->set('title', 'VPN drops every morning')
            ->set('description', 'Connection resets around 9am daily.')
            ->set('priority', TicketPriority::Normal->value)
            ->call('save');

        $this->assertDatabaseHas('tickets', [
            'title' => 'VPN drops every morning',
            'requester_id' => $this->requester->id,
        ]);
    }

    public function test_the_form_rejects_a_blank_title(): void
    {
        Livewire::actingAs($this->requester)
            ->test(TicketForm::class)
            ->set('title', '')
            ->set('description', 'Something happened.')
            ->set('priority', TicketPriority::Normal->value)
            ->call('save')
            ->assertHasErrors(['title' => 'required']);
    }

    public function test_a_manager_can_assign_a_ticket_from_the_form(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $technician = User::factory()->create();

        Livewire::actingAs($this->manager)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->call('assign')
            ->assertSet('ticket.status', TicketStatus::Assigned);

        $this->assertSame(TicketStatus::Assigned, $ticket->refresh()->status);
    }

    public function test_an_illegal_transition_flashes_an_error_instead_of_crashing(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        Livewire::actingAs($this->manager)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->call('close')
            ->assertSee('This transition is not allowed from the current status.');

        $this->assertSame(TicketStatus::Open, $ticket->refresh()->status);
    }
}
