<?php

namespace Functional\Tickets\Tests\Feature\Mcp;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Mcp\Resources\TicketRulesResource;
use Functional\Tickets\Mcp\Tools\CreateTicketTool;
use Functional\Tickets\Mcp\Tools\SearchTicketsTool;
use Functional\Tickets\Mcp\Tools\TransitionTicketTool;
use Functional\Tickets\Mcp\Tools\ViewTicketTool;
use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Tests\Concerns\CallsTicketsMcpServer;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketsServerToolsTest extends TestCase
{
    use CallsTicketsMcpServer;
    use CreatesTicketProfiles;
    use RefreshDatabase;

    #[Test]
    public function it_advertises_every_tool_and_the_rules_resource(): void
    {
        $requester = $this->createRequester();

        $this->callingAs($requester)->tools()->assertRegistered([
            SearchTicketsTool::class,
            ViewTicketTool::class,
            CreateTicketTool::class,
            TransitionTicketTool::class,
        ]);

        $this->callingAs($requester)->resources()->assertRegistered(TicketRulesResource::class);

        $this->callingAs($requester)
            ->tool(SearchTicketsTool::class)
            ->assertName('search-tickets');
    }

    #[Test]
    public function it_shows_a_requester_only_the_tickets_they_opened(): void
    {
        $requester = $this->createRequester();

        Ticket::factory()->count(3)->for($requester, 'requester')->create();
        Ticket::factory()->count(4)->assignedToTechnician($this->createTechnician())->create();

        $this->callingAs($requester)
            ->tool(SearchTicketsTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json
                ->where('count', 3)
                ->has('tickets', 3, fn (AssertableJson $ticket): AssertableJson => $ticket
                    ->where('requester.id', $requester->getKey())
                    ->etc())
                ->etc());
    }

    #[Test]
    public function it_shows_a_manager_every_ticket(): void
    {
        Ticket::factory()->count(3)->for($this->createRequester(), 'requester')->create();
        Ticket::factory()->count(4)->assignedToTechnician($this->createTechnician())->create();

        $this->callingAs($this->createManager())
            ->tool(SearchTicketsTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->where('count', 7)->etc());
    }

    #[Test]
    public function it_refuses_a_search_from_a_user_holding_no_ticket_permission(): void
    {
        $this->callingAs(User::factory()->create())
            ->tool(SearchTicketsTool::class)
            ->assertHasErrors(['This action is unauthorized.']);
    }

    #[Test]
    public function it_hides_a_ticket_outside_the_perimeter_even_when_its_id_is_known(): void
    {
        $foreignTicket = Ticket::factory()->create();

        $this->callingAs($this->createRequester())
            ->tool(ViewTicketTool::class, ['ticket_id' => $foreignTicket->getKey()])
            ->assertHasErrors(["No ticket [{$foreignTicket->getKey()}] is visible to you."]);
    }

    #[Test]
    public function it_reads_back_a_ticket_inside_the_perimeter(): void
    {
        $requester = $this->createRequester();
        $ticket = Ticket::factory()->for($requester, 'requester')->create();

        $this->callingAs($requester)
            ->tool(ViewTicketTool::class, ['ticket_id' => $ticket->getKey()])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json
                ->where('id', $ticket->getKey())
                ->where('title', $ticket->title)
                ->where('requester.email', $requester->email)
                ->etc());
    }

    #[Test]
    public function it_finds_a_ticket_it_has_just_created_through_the_search_tool(): void
    {
        $requester = $this->createRequester();

        $this->callingAs($requester)
            ->tool(CreateTicketTool::class, [
                'title' => 'The printer eats every third page',
                'description' => 'Two pages come out, the third one jams.',
                'priority' => TicketPriority::High->value,
            ])
            ->assertOk()
            ->assertSee('The printer eats every third page');

        $this->callingAs($requester)
            ->tool(SearchTicketsTool::class, ['term' => 'printer', 'priority' => TicketPriority::High->value])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json
                ->where('count', 1)
                ->has('tickets.0', fn (AssertableJson $ticket): AssertableJson => $ticket
                    ->where('title', 'The printer eats every third page')
                    ->where('status', TicketStatus::Open->value)
                    ->where('priority', TicketPriority::High->value)
                    ->where('requester.id', $requester->getKey())
                    ->etc())
                ->etc());

        $this->assertDatabaseHas(Ticket::class, [
            'title' => 'The printer eats every third page',
            'requester_id' => $requester->getKey(),
        ]);
    }

    #[Test]
    public function it_refuses_a_priority_outside_the_enum(): void
    {
        $this->callingAs($this->createRequester())
            ->tool(CreateTicketTool::class, [
                'title' => 'Coffee machine offline',
                'description' => 'It hums but pours nothing.',
                'priority' => 'apocalyptic',
            ])
            ->assertHasErrors();

        $this->assertDatabaseCount(Ticket::class, 0);
    }

    #[Test]
    public function it_refuses_a_creation_from_a_user_who_may_not_open_tickets(): void
    {
        $this->callingAs($this->createManager())
            ->tool(CreateTicketTool::class, [
                'title' => 'Badge reader unresponsive',
                'description' => 'Nothing happens when the badge is presented.',
            ])
            ->assertHasErrors(['This action is unauthorized.']);

        $this->assertDatabaseCount(Ticket::class, 0);
    }

    #[Test]
    public function it_publishes_the_rules_an_agent_would_otherwise_have_to_guess(): void
    {
        $this->callingAs($this->createRequester())
            ->resource(TicketRulesResource::class)
            ->assertOk()
            ->assertSee([
                TicketPriority::Critical->value,
                (string) TicketPriority::Critical->slaHours(),
                TicketStatus::InProgress->value,
                'title',
                'description',
            ]);
    }
}
