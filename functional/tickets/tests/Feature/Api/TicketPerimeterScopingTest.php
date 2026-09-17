<?php

namespace Functional\Tickets\Tests\Feature\Api;

use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketPerimeterScopingTest extends TestCase
{
    use CreatesTicketProfiles;
    use RefreshDatabase;

    private User $requester;

    private User $technician;

    private User $manager;

    /**
     * Ten tickets laid out so the three perimeters cannot produce the same count by
     * accident: 5 belong to the requester, 7 are visible to the technician, 10 in total.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->requester = $this->createRequester();
        $this->technician = $this->createTechnician();
        $this->manager = $this->createManager();

        Ticket::factory()->count(3)->for($this->requester, 'requester')->create();
        Ticket::factory()->count(2)->for($this->requester, 'requester')->assignedToTechnician($this->technician)->create();
        Ticket::factory()->count(4)->assignedToTechnician($this->technician)->create();
        Ticket::factory()->for($this->technician, 'requester')->assignedToTechnician()->create();
    }

    #[Test]
    public function it_shows_a_requester_only_the_tickets_they_opened(): void
    {
        Sanctum::actingAs($this->requester);

        $response = $this->postJson('/api/v1/tickets/search', []);

        $response->assertSuccessful()->assertJsonCount(5, 'data');
        $this->assertSame(
            [$this->requester->getKey()],
            Ticket::whereIn('id', $this->returnedIds($response->json('data')))->pluck('requester_id')->unique()->values()->all(),
        );
    }

    #[Test]
    public function it_shows_a_technician_the_tickets_assigned_to_them_plus_their_own(): void
    {
        Sanctum::actingAs($this->technician);

        $this->postJson('/api/v1/tickets/search', [])
            ->assertSuccessful()
            ->assertJsonCount(7, 'data');
    }

    #[Test]
    public function it_shows_a_manager_every_ticket(): void
    {
        Sanctum::actingAs($this->manager);

        $this->postJson('/api/v1/tickets/search', [])
            ->assertSuccessful()
            ->assertJsonCount(10, 'data');
    }

    #[Test]
    public function it_hides_a_ticket_outside_the_perimeter_even_when_its_id_is_known(): void
    {
        $foreignTicket = Ticket::factory()->create();
        Sanctum::actingAs($this->requester);

        $this->postJson('/api/v1/tickets/search', [
            'search' => ['filters' => [['field' => 'id', 'operator' => '=', 'value' => $foreignTicket->getKey()]]],
        ])
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_refuses_a_search_from_a_user_holding_no_ticket_permission(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/tickets/search', [])->assertForbidden();
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<int>
     */
    private function returnedIds(array $rows): array
    {
        return array_map(static fn (array $row): int => $row['id'], $rows);
    }
}
