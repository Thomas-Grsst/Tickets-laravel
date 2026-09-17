<?php

namespace Functional\Tickets\Tests\Feature\Mcp;

use Functional\Tickets\Models\Ticket;
use Functional\Tickets\Tests\Concerns\CreatesTicketProfiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The server is reached over the same guard as the REST API, so the identity a tool runs
 * under is a real authenticated user and not a header the agent made up.
 */
class McpEndpointAuthenticationTest extends TestCase
{
    use CreatesTicketProfiles;
    use RefreshDatabase;

    private const ENDPOINT = '/mcp/tickets';

    #[Test]
    public function it_refuses_an_unauthenticated_call(): void
    {
        $this->rpc('initialize')->assertUnauthorized();
    }

    #[Test]
    public function it_answers_a_call_authenticated_with_a_sanctum_token(): void
    {
        Sanctum::actingAs($this->createRequester());

        $this->rpc('initialize')
            ->assertSuccessful()
            ->assertJsonPath('result.serverInfo.name', 'Tickets');
    }

    #[Test]
    public function it_runs_a_tool_under_the_perimeters_of_the_token_holder(): void
    {
        $requester = $this->createRequester();
        Ticket::factory()->count(2)->for($requester, 'requester')->create();
        Ticket::factory()->count(3)->create();

        Sanctum::actingAs($requester);

        $this->rpc('tools/call', ['name' => 'search-tickets', 'arguments' => []], 'search-tickets')
            ->assertSuccessful()
            ->assertJsonPath('result.structuredContent.count', 2);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function rpc(string $method, array $params = [], ?string $name = null): TestResponse
    {
        $headers = [
            'MCP-Protocol-Version' => '2025-11-25',
            'Mcp-Method' => $method,
            'Accept' => 'application/json, text/event-stream',
        ];

        if ($name !== null) {
            $headers['Mcp-Name'] = $name;
        }

        return $this->postJson(self::ENDPOINT, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params === [] ? ['protocolVersion' => '2025-11-25'] : $params,
        ], $headers);
    }
}
