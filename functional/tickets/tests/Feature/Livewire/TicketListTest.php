<?php

namespace Functional\Tickets\Tests\Feature\Livewire;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Livewire\TicketList;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Functional\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketListTest extends TestCase
{
    use CreatesTicketProfiles;
    use RefreshDatabase;

    #[Test]
    public function it_lists_only_the_tickets_a_requester_opened(): void
    {
        $requester = $this->createRequester();
        $own = Ticket::factory()->count(2)->for($requester, 'requester')->create();
        $foreign = Ticket::factory()->create();

        Livewire::actingAs($requester)
            ->test(TicketList::class)
            ->assertSee($own->first()->title)
            ->assertDontSee($foreign->title)
            ->assertViewHas('tickets', fn (LengthAwarePaginator $tickets): bool => $tickets->total() === 2);
    }

    #[Test]
    public function it_lists_every_ticket_for_a_manager(): void
    {
        $manager = $this->createManager();
        Ticket::factory()->count(4)->create();

        Livewire::actingAs($manager)
            ->test(TicketList::class)
            ->assertViewHas('tickets', fn (LengthAwarePaginator $tickets): bool => $tickets->total() === 4);
    }

    #[Test]
    public function it_narrows_the_list_to_the_selected_status(): void
    {
        $manager = $this->createManager();
        Ticket::factory()->count(3)->create(['status' => TicketStatus::Open]);
        Ticket::factory()->count(2)->create(['status' => TicketStatus::Resolved]);

        Livewire::actingAs($manager)
            ->test(TicketList::class)
            ->set('status', TicketStatus::Resolved->value)
            ->assertViewHas('tickets', fn (LengthAwarePaginator $tickets): bool => $tickets->total() === 2);
    }

    #[Test]
    public function it_narrows_the_list_to_the_selected_priority(): void
    {
        $manager = $this->createManager();
        Ticket::factory()->count(3)->create(['priority' => TicketPriority::Low]);
        Ticket::factory()->create(['priority' => TicketPriority::Critical]);

        Livewire::actingAs($manager)
            ->test(TicketList::class)
            ->set('priority', TicketPriority::Critical->value)
            ->assertViewHas('tickets', fn (LengthAwarePaginator $tickets): bool => $tickets->total() === 1);
    }

    #[Test]
    public function it_returns_to_the_first_page_when_a_filter_changes(): void
    {
        $manager = $this->createManager();
        Ticket::factory()->count(30)->create(['status' => TicketStatus::Open]);

        Livewire::actingAs($manager)
            ->test(TicketList::class)
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('status', TicketStatus::Open->value)
            ->assertSet('paginators.page', 1);
    }

    #[Test]
    public function it_paginates_the_list_at_twenty_five_rows(): void
    {
        $manager = $this->createManager();
        Ticket::factory()->count(30)->create();

        $component = Livewire::actingAs($manager)->test(TicketList::class);

        $component->assertViewHas('tickets', fn (LengthAwarePaginator $tickets): bool => $tickets->count() === 25 && $tickets->total() === 30);
        $component->call('gotoPage', 2)
            ->assertViewHas('tickets', fn (LengthAwarePaginator $tickets): bool => $tickets->count() === 5);
    }

    #[Test]
    public function it_sorts_by_a_whitelisted_column_and_toggles_the_direction(): void
    {
        $manager = $this->createManager();
        Ticket::factory()->create(['title' => 'Alpha printer fault']);
        Ticket::factory()->create(['title' => 'Zulu printer fault']);

        Livewire::actingAs($manager)
            ->test(TicketList::class)
            ->call('sortBy', 'title')
            ->assertSet('sort', 'title')
            ->assertSet('direction', 'asc')
            ->assertViewHas('tickets', fn (LengthAwarePaginator $tickets): bool => $tickets->first()->title === 'Alpha printer fault')
            ->call('sortBy', 'title')
            ->assertSet('direction', 'desc')
            ->assertViewHas('tickets', fn (LengthAwarePaginator $tickets): bool => $tickets->first()->title === 'Zulu printer fault');
    }

    #[Test]
    public function it_ignores_a_sort_on_a_column_that_is_not_whitelisted(): void
    {
        $manager = $this->createManager();
        Ticket::factory()->count(2)->create();

        Livewire::actingAs($manager)
            ->test(TicketList::class)
            ->call('sortBy', 'description')
            ->assertSet('sort', 'created_at');
    }

    #[Test]
    public function it_falls_back_to_the_default_sort_when_the_url_carries_an_unknown_column(): void
    {
        $manager = $this->createManager();
        $older = Ticket::factory()->create(['created_at' => now()->subDays(5)]);
        $newer = Ticket::factory()->create(['created_at' => now()->subDay()]);

        Livewire::actingAs($manager)
            ->test(TicketList::class, ['sort' => 'requester_id'])
            ->assertViewHas('tickets', fn (LengthAwarePaginator $tickets): bool => $tickets->first()->is($newer) && $tickets->last()->is($older));
    }

    #[Test]
    public function it_refuses_the_list_to_a_guest(): void
    {
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function it_serves_the_list_route_to_an_authenticated_requester(): void
    {
        $requester = $this->createRequester();
        Ticket::factory()->for($requester, 'requester')->create();

        $this->actingAs($requester)
            ->get(route('tickets.index'))
            ->assertSuccessful()
            ->assertSeeLivewire(TicketList::class);
    }

    #[Test]
    public function it_counts_the_comments_of_each_listed_ticket(): void
    {
        $manager = $this->createManager();
        $ticket = Ticket::factory()->create();
        $ticket->comments()->createMany([
            ['author_id' => User::factory()->create()->getKey(), 'body' => 'First look.'],
            ['author_id' => User::factory()->create()->getKey(), 'body' => 'Second look.'],
        ]);

        Livewire::actingAs($manager)
            ->test(TicketList::class)
            ->assertViewHas('tickets', fn (LengthAwarePaginator $tickets): bool => $tickets->first()->comments_count === 2);
    }
}
