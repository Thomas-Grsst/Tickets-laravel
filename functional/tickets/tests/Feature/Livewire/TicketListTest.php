<?php

namespace Functional\Tickets\Tests\Feature\Livewire;

use Functional\Tickets\Enums\TicketPermission;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Livewire\TicketList;
use Functional\Tickets\Models\Ticket;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketListTest extends TestCase
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

    public function test_it_only_lists_the_authenticated_users_tickets(): void
    {
        $other = User::factory()->create();
        Ticket::factory()->for($this->requester, 'requester')->count(2)->create();
        Ticket::factory()->for($other, 'requester')->create();

        Livewire::actingAs($this->requester)
            ->test(TicketList::class)
            ->assertViewHas('tickets', fn ($tickets) => $tickets->total() === 2);
    }

    public function test_filtering_by_status_resets_pagination_to_the_first_page(): void
    {
        Ticket::factory()->for($this->requester, 'requester')->create(['status' => TicketStatus::Open]);
        Ticket::factory()->for($this->requester, 'requester')->create(['status' => TicketStatus::Closed]);

        Livewire::actingAs($this->requester)
            ->test(TicketList::class)
            ->call('gotoPage', 2)
            ->set('status', TicketStatus::Closed->value)
            ->assertSet('paginators.page', 1)
            ->assertViewHas('tickets', fn ($tickets) => $tickets->total() === 1);
    }

    public function test_sorting_only_accepts_a_whitelisted_column(): void
    {
        Ticket::factory()->for($this->requester, 'requester')->create();

        Livewire::actingAs($this->requester)
            ->test(TicketList::class)
            ->call('sortBy', 'email')
            ->assertSet('sort', 'created_at');
    }

    public function test_sorting_by_the_same_column_twice_flips_the_direction(): void
    {
        Ticket::factory()->for($this->requester, 'requester')->create();

        Livewire::actingAs($this->requester)
            ->test(TicketList::class)
            ->call('sortBy', 'title')
            ->assertSet('direction', 'asc')
            ->call('sortBy', 'title')
            ->assertSet('direction', 'desc');
    }

    public function test_the_number_of_queries_does_not_grow_with_the_number_of_tickets(): void
    {
        Ticket::factory()->for($this->requester, 'requester')->count(3)->create();

        // Warms up caches (e.g. spatie/laravel-permission's) that only query once per process,
        // so they don't inflate the count of whichever scenario happens to run first.
        Livewire::actingAs($this->requester)->test(TicketList::class);

        $queriesForThree = $this->countQueriesFor(fn () => Livewire::actingAs($this->requester)->test(TicketList::class));

        Ticket::factory()->for($this->requester, 'requester')->count(10)->create();

        $queriesForThirteen = $this->countQueriesFor(fn () => Livewire::actingAs($this->requester)->test(TicketList::class));

        $this->assertSame($queriesForThree, $queriesForThirteen);
    }

    private function countQueriesFor(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $callback();

        $count = count(DB::getQueryLog());

        DB::disableQueryLog();
        DB::flushQueryLog();

        return $count;
    }
}
