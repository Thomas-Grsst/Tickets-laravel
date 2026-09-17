<?php

namespace Functional\Tickets\Tests\Feature\Livewire;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Livewire\TicketForm;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketFormTest extends TestCase
{
    use CreatesTicketProfiles;
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_ticket_for_the_requester_filling_the_form(): void
    {
        $requester = $this->createRequester();

        Livewire::actingAs($requester)
            ->test(TicketForm::class)
            ->set('title', 'Laptop fan runs at full speed')
            ->set('description', 'It starts a minute after boot and never stops.')
            ->set('priority', TicketPriority::High->value)
            ->call('save');

        $this->assertDatabaseHas('tickets', [
            'title' => 'Laptop fan runs at full speed',
            'requester_id' => $requester->getKey(),
            'status' => TicketStatus::Open->value,
            'priority' => TicketPriority::High->value,
        ]);
    }

    #[Test]
    public function it_refuses_to_save_a_ticket_without_a_title(): void
    {
        Livewire::actingAs($this->createRequester())
            ->test(TicketForm::class)
            ->set('title', '')
            ->set('description', 'A description without a title.')
            ->call('save')
            ->assertHasErrors(['title' => 'required']);

        $this->assertDatabaseCount('tickets', 0);
    }

    #[Test]
    public function it_defaults_a_new_ticket_to_the_normal_priority(): void
    {
        Livewire::actingAs($this->createRequester())
            ->test(TicketForm::class)
            ->assertSet('priority', TicketPriority::Normal->value)
            ->assertSet('isEditable', true);
    }

    #[Test]
    public function it_opens_a_requester_own_ticket_read_only(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create();

        Livewire::actingAs($requester)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->assertSet('title', $ticket->title)
            ->assertSet('isEditable', false);
    }

    #[Test]
    public function it_refuses_to_open_a_ticket_outside_the_perimeter(): void
    {
        $requester = $this->createRequester();
        $foreignTicket = Ticket::factory()->create();

        Livewire::actingAs($requester)
            ->test(TicketForm::class, ['ticket' => $foreignTicket])
            ->assertForbidden();
    }

    #[Test]
    public function it_lets_a_manager_edit_an_existing_ticket(): void
    {
        $manager = $this->createManager();
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($manager)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->assertSet('isEditable', true)
            ->set('title', 'Rewritten by the manager')
            ->call('save');

        $this->assertSame('Rewritten by the manager', $ticket->fresh()->title);
    }

    #[Test]
    public function it_assigns_the_ticket_to_the_manager_pressing_the_assign_button(): void
    {
        $manager = $this->createManager();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        Livewire::actingAs($manager)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->call('assign');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->getKey(),
            'status' => TicketStatus::Assigned->value,
            'assigned_technician_id' => $manager->getKey(),
        ]);
    }

    #[Test]
    public function it_walks_a_ticket_through_the_whole_lifecycle_from_the_form(): void
    {
        $manager = $this->createManager();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $component = Livewire::actingAs($manager)->test(TicketForm::class, ['ticket' => $ticket]);

        $component->call('assign');
        $component->call('startProgress');
        $component->call('resolve');
        $component->call('reopen');
        $component->call('resolve');
        $component->call('close');

        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
    }

    #[Test]
    public function it_unassigns_a_ticket_from_the_form(): void
    {
        $manager = $this->createManager();
        $ticket = Ticket::factory()->assignedToTechnician()->create();

        Livewire::actingAs($manager)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->call('unassign');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->getKey(),
            'status' => TicketStatus::Open->value,
            'assigned_technician_id' => null,
        ]);
    }

    #[Test]
    public function it_leaves_the_ticket_alone_when_the_pressed_transition_is_illegal(): void
    {
        $manager = $this->createManager();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Closed]);

        Livewire::actingAs($manager)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->call('assign')
            ->assertSee(__('tickets::messages.form.error.transition'));

        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
    }

    #[Test]
    public function it_offers_only_the_transitions_the_current_status_allows(): void
    {
        $manager = $this->createManager();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Resolved]);

        Livewire::actingAs($manager)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->assertViewHas('availableActions', fn (array $actions): bool => array_column($actions, 'method') === ['reopen', 'close']);
    }

    #[Test]
    public function it_offers_no_transition_on_a_closed_ticket(): void
    {
        $manager = $this->createManager();
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Closed]);

        Livewire::actingAs($manager)
            ->test(TicketForm::class, ['ticket' => $ticket])
            ->assertViewHas('availableActions', []);
    }

    #[Test]
    public function it_offers_no_transition_on_a_ticket_that_does_not_exist_yet(): void
    {
        Livewire::actingAs($this->createRequester())
            ->test(TicketForm::class)
            ->assertViewHas('availableActions', []);
    }

    #[Test]
    public function it_refuses_the_create_form_to_a_user_without_the_create_permission(): void
    {
        $manager = $this->createManager();

        Livewire::actingAs($manager)
            ->test(TicketForm::class)
            ->assertForbidden();
    }
}
