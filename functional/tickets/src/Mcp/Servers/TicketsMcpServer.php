<?php

namespace Functional\Tickets\Mcp\Servers;

use Functional\Tickets\Mcp\Resources\TicketInputRulesResource;
use Functional\Tickets\Mcp\Tools\CreateTicketTool;
use Functional\Tickets\Mcp\Tools\SearchTicketsTool;
use Functional\Tickets\Mcp\Tools\ViewTicketTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('XEFI Academy Tickets')]
#[Version('1.0.0')]
#[Instructions('Search, view and create support tickets on behalf of the authenticated user. Read the ticket-input-rules resource before creating a ticket.')]
class TicketsMcpServer extends Server
{
    protected array $tools = [
        SearchTicketsTool::class,
        ViewTicketTool::class,
        CreateTicketTool::class,
    ];

    protected array $resources = [
        TicketInputRulesResource::class,
    ];
}
