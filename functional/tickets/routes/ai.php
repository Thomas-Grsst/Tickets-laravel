<?php

use Functional\Tickets\Mcp\Servers\TicketsServer;
use Laravel\Mcp\Facades\Mcp;

/**
 * The same guard the REST API is behind, so an agent authenticates with a Sanctum bearer
 * token issued to a real user and every tool runs under that user's perimeters.
 */
Mcp::web('mcp/tickets', TicketsServer::class)
    ->middleware('auth:sanctum')
    ->name('mcp.tickets');

Mcp::local('tickets', TicketsServer::class);
