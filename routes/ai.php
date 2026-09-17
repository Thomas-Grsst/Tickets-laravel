<?php

use Functional\Tickets\Mcp\Servers\TicketsMcpServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/tickets', TicketsMcpServer::class)->middleware('auth:sanctum');
