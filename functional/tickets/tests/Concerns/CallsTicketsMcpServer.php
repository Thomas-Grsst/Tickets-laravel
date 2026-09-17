<?php

namespace Functional\Tickets\Tests\Concerns;

use Functional\Tickets\Mcp\Servers\TicketsServer;
use Functional\Users\Models\User;
use Laravel\Mcp\Server\Testing\PendingTestResponse;

/**
 * The package's own `TicketsServer::actingAs(...)` entry point goes through `__callStatic`,
 * whose union return type hides the pending response from static analysis; building the
 * pending response by hand is the same call with a type a reader and PHPStan can both follow.
 */
trait CallsTicketsMcpServer
{
    protected function callingAs(User $user): PendingTestResponse
    {
        return (new PendingTestResponse($this->app, TicketsServer::class))->actingAs($user);
    }
}
